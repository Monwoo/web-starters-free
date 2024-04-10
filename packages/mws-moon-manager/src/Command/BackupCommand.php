<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖,
// build by Miguel Monwoo, service@monwoo.com
namespace MWS\MoonManagerBundle\Command;

use DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

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
    ) {
        parent::__construct();
        $this->filesystem = new Filesystem();
    }

    protected function configure(): void
    {
        // TODO : load backup from 'Folder' or 'Multi-file form folder input' or 'zip'
        // $this->addOption(
        //     'userLogin', '-l', InputArgument::OPTIONAL,
        //     "Login de l'utilisateur", $this->userLogin
        // );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // $this->userLogin = $input->getOption("userLogin");
        // $minBackupDelay = 24 * 60 * 60; // attente minimum de 24 heures entre chaque backups...
        // $minBackupDelay = 5; // attente minimum de 5 secondes
        $minBackupDelay = 60; // attente minimum de 1 minute

        $cmdStatus = Command::SUCCESS;

        $projectDir = $this->params->get('kernel.project_dir');
        $backupsDir = "$projectDir/bckup";
        $timestamp = time();

        // check last backup timestamp (faster to read/write than than sorting possible huge backup dir folder....)
        $lastBackupTimestamp = 0;
        $lastTimestampFile = "$backupsDir/timestamp.txt";
        if (file_exists($lastTimestampFile)) {
            $lastBackupTimestamp = intval(file_get_contents($lastTimestampFile));
        }
        $lastBckupDelta = $lastBackupTimestamp
        ? $timestamp - $lastBackupTimestamp
        : $minBackupDelay;
        if ($lastBckupDelta < $minBackupDelay) {
            // dd($lastBckupDelta);
            // backup already done, no need to redo.
            return $cmdStatus;
        }
        $backupFolderName = (new DateTime())
        ->setTimestamp($timestamp)->format('Ymd_His');
        $currentBackupDir = "$backupsDir/$backupFolderName";

        $backupDatabaseFile = "$currentBackupDir/data.$timestamp.sqlite";
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
        $uploadSrc = "$projectDir/public/uploads/messages/tchats";
        // dd($uploadSrc);

        $this->recursive_copy($uploadSrc, "$currentBackupDir/public/uploads/messages/tchats");

        // TODO : remove and clean copy olders than 60 days ? (to keep spaces...)

        $output->writeln([
            "<info>Did backup to '$backupDatabaseFile'</info>",
        ]);
        file_put_contents($lastTimestampFile, $timestamp);

        return $cmdStatus;
    }

    function recursive_copy($src,$dst) {
        // https://gist.github.com/gserrano/4c9648ec9eb293b9377b
        if (!file_exists($src)) {
            return;
        }
        $dir = opendir($src);
        @mkdir($dst, 0777, true);
        while(( $file = readdir($dir)) ) {
            if (( $file != '.' ) && ( $file != '..' )) {
                if ( is_dir($src . '/' . $file) ) {
                    $this->recursive_copy($src .'/'. $file, $dst .'/'. $file);
                }
                else {
                    copy($src .'/'. $file,$dst .'/'. $file);
                }
            }
        }
        closedir($dir);
    }
    
}
