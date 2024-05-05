<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖,
// build by Miguel Monwoo, service@monwoo.com
namespace MWS\MoonManagerBundle\Command;

use DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\String\Slugger\SluggerInterface;

#[AsCommand(
    name: 'mws:backup',
    description: 'Will do some backups.',
    hidden: false,
    // aliases: ['backup:sync']
)]
class BackupCommand extends Command
{
    private Filesystem $filesystem;

    public function __construct(
        protected ParameterBagInterface $params,
        protected SluggerInterface $slugger,
        // TIPS : must be end of params to avoid error
        // Cannot autowire service "MWS\MoonManagerBundle\Command\BackupCommand": arg  
        // ument "$backupName" of method "__construct()" is type-hinted "string", you  
        // should configure its value explicitly. 
        protected ?String $backupName = null,
    ) {
        parent::__construct();
        $this->filesystem = new Filesystem();
    }

    protected function configure(): void
    {
        // TODO : load backup from 'Folder' or 'Multi-file form folder input' or 'zip'
        // $this->addOption(
        //     'backupName', '-b', InputArgument::OPTIONAL,
        //     "Nom du backup", $this->backupName
        // );
        $this->addArgument(
            'backupName',
            InputArgument::OPTIONAL,
            "Nom du backup",
            $this->backupName
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // $this->backupName = $input->getOption("backupName");
        $this->backupName = $input->getArgument("backupName");
        $this->backupName = $this->slugger->slug(
            $this->backupName ?? ''
        );

        $cmdStatus = Command::SUCCESS;
        $backupStack = json_decode($_SERVER['CONFIG_BCKUP_MAX_STACK'] ?? -1);
        if ($backupStack < 0) {
            $backupStack = PHP_INT_MAX;
        }
        if ($backupStack === 0) {
            $output->writeln([
                "<info>Backup deactivated, check 'CONFIG_BCKUP_MAX_STACK'</info>",
            ]);
            return $cmdStatus;
        }
        $projectDir = $this->params->get('kernel.project_dir');
        $backupsDir = "$projectDir/bckup";
        $timestamp = time();
        $lastTimestampFile = "$backupsDir/timestamp.txt";
        // if (file_exists($lastTimestampFile)) { // TIPS : use from LISTENER...
        //     $lastBackupTimestamp = intval(file_get_contents($lastTimestampFile));
        // }

        // $minBackupDelay = 24 * 60 * 60; // attente minimum de 24 heures entre chaque backups...
        // $minBackupDelay = 5; // attente minimum de 5 secondes
        // $minBackupDelay = 60; // attente minimum de 1 minute
        // TODO : wrong optimisation ? Can MISS backups if delay bigger
        //        than data updates....
        // // TODO : refactor : delay usable in LISTENER to trigger cmd instead...
        // $minBackupDelay = json_decode($_SERVER['CONFIG_BCKUP_AUTOSAVE_MIN_DELAY'] ?? -1);
        // // check last backup timestamp (faster to read/write than than sorting possible huge backup dir folder....)
        // $lastBackupTimestamp = 0;
        // $lastTimestampFile = "$backupsDir/timestamp.txt";
        // if (file_exists($lastTimestampFile)) {
        //     $lastBackupTimestamp = intval(file_get_contents($lastTimestampFile));
        // }
        // $lastBckupDelta = $lastBackupTimestamp
        // ? $timestamp - $lastBackupTimestamp
        // : $minBackupDelay;
        // if ($lastBckupDelta < $minBackupDelay) {
        //     // dd($lastBckupDelta);
        //     // backup already done, no need to redo.
        //     // TODO : wrong meaning for bckup tool ? will miss data if done before wipe out...
        //     return $cmdStatus;
        // }
        $backupFolderName = (new DateTime())
            ->setTimestamp($timestamp)->format('Ymd_His');
        if ($this->backupName && strlen($this->backupName)) {
            $backupFolderName .= "-" . $this->backupName;
        }
        $currentBackupDir = "$backupsDir/$backupFolderName";

        // $backupDatabaseFile = "$currentBackupDir/data.$timestamp.sqlite";
        $backupDatabaseFile = "$currentBackupDir/data.db.sqlite"; // Need to keep name for backup reload easiest reloads
        // TODO : use kernel.cache_dir instead of presuming of 'var' folder ?
        $databaseFile = "$projectDir/var/data.db.sqlite"; // TODO : dynamic App db name ? Clustering DB by domains ?

        $this->filesystem->mkdir($currentBackupDir);

        $this->filesystem->copy($databaseFile, $backupDatabaseFile);

        // copy Messages medias
        // $finder = new Finder();
        // $finder->files()->in( $this->abspath )->exclude( $exclude )->notPath( '/.*\/node_modules\/.*/' );
        // $finder->copy("$projectDir/");
        // $uploadSrc = $this->params->get('vich_uploader.mappings.message_tchats_upload.upload_destination');
        // $uploadSrc = $this->params->get('vich_uploader');
        $subFolder = $this->params->get('mws_moon_manager.uploadSubFolder') ?? '';
        $uploadSrc = "$projectDir/$subFolder";
        // dd($uploadSrc);

        $this->recursive_copy($uploadSrc, "$currentBackupDir");

        // TODO : remove and clean copy olders than 60 days ? (to keep spaces...)

        file_put_contents($lastTimestampFile, $timestamp);
        $output->writeln([
            "<info>Did backup to '$backupDatabaseFile'</info>",
        ]);

        // Clean up too olds backups :
        $backupsDir = "$projectDir/bckup";
        $finder = [];
        if (!empty(glob($backupsDir))) {
            $finder = new Finder();
            $finder->directories()->in($backupsDir)
                ->ignoreDotFiles(true)
                ->ignoreUnreadableDirs()
                ->depth(0);

            $finder->sort(function (SplFileInfo $a, SplFileInfo $b): int {
                // return strcmp($a->getRealPath(), $b->getRealPath());
                return strcmp($b->getRealPath(), $a->getRealPath());
            });
            $finder = iterator_to_array($finder, false);
        }

        $toClean = array_slice($finder, $backupStack);
        $output->writeln([
            "<info>Backup to clean : </info>" . count($toClean),
        ]);
        /** @var SplFileInfo $f */
        foreach ($toClean as $f) {
            $this->filesystem->remove($f->getPathname());
        }

        return $cmdStatus;
    }

    function recursive_copy($src, $dst)
    {
        // https://gist.github.com/gserrano/4c9648ec9eb293b9377b
        if (!file_exists($src)) {
            return;
        }
        $dir = opendir($src);
        @mkdir($dst, 0777, true);
        while (($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->recursive_copy($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
    }
}
