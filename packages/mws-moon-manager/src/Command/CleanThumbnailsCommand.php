<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖,
// build by Miguel Monwoo, service@monwoo.com
namespace MWS\MoonManagerBundle\Command;

use DateTime;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use MWS\MoonManagerBundle\Entity\MwsTimeSlot;
use MWS\MoonManagerBundle\Entity\MwsUser;
use MWS\MoonManagerBundle\Repository\MwsTimeSlotUploadRepository;
use MWS\MoonManagerBundle\Services\MaxPriceTagManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'mws:clean-thumbnails',
    description: 'TODO. ex : \n
        time php bin/console mws:clean-thumbnails',
    hidden: false,
    // aliases: ['app:make-user']
)]
class CleanThumbnailsCommand extends Command
{
    public function __construct(
        protected ManagerRegistry $registry,
        protected EntityManagerInterface $em,
        protected MwsTimeSlotUploadRepository $mwsTimeSlotUploadRepository,
    ) {
        // TIPS : best practices recommend to call the parent constructor first and
        // then set your own properties. That wouldn't work in this case
        // because configure() needs the properties set in this constructor
        // $this->requirePassword = $requirePassword;

        parent::__construct();
    }

    protected function configure(): void
    {
        // $this->addOption(
        //     'userLogin', '-l', InputArgument::OPTIONAL,
        //     "Login de l'utilisateur", $this->userLogin
        // );
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // $this->userLogin = $input->getOption("userLogin");

        $cmdStatus = Command::SUCCESS;

        $qb = $this->em->createQueryBuilder()
        ->select('s')                
        ->from(MwsTimeSlot::class, 's');
        $query = $qb->getQuery();
        // dump($query->getSql());
        $resp = $query->execute();
        // dd($resp);
        // creates a new progress bar (50 units)
        // $progressBar = new ProgressBar($output, 50);
        // https://symfony.com/doc/current/components/console/helpers/progressbar.html
        $progressBar = new ProgressBar($output, count($resp));
        // this redraws the screen every 100 iterations, but sets additional limits:
        // don't redraw slower than 200ms (0.2) or faster than 100ms (0.1)
        $progressBar->setRedrawFrequency(100);
        $progressBar->maxSecondsBetweenRedraws(0.2);
        $progressBar->minSecondsBetweenRedraws(0.1);

        // https://symfony.com/doc/current/components/console/helpers/progressbar.html#custom-messages
        ProgressBar::setFormatDefinition('custom', ' %current%/%max% -- %message% [%bar%]');
        $progressBar->setFormat('custom');
        $progressBar->setMessage('Updating time slots ...');

        // starts and displays the progress bar
        $progressBar->start();
        // dd($resp ); // TIPS : not debuggable with web remove since ignoring output...
        // TODO : show report and read outputs for web runs ?
        /** @var MwsTimeSlot $s */
        foreach ($resp as $s) {
            $thumb = $s->getThumbnailJpeg();
            // dump($thumb);

            if (str_starts_with($thumb ?? '', '/')) {
                $thumbParts = explode('/', $thumb);
                $upload = $this->mwsTimeSlotUploadRepository->findOneBy([
                    // TODO : getMediaOriginalName might be null if using ReplacingFile instead of uploader...
                    // 'mediaOriginalName' => $messageTchatUpload->getMediaOriginalName(),
                    'mediaName' => end($thumbParts),
                ]);
                if ($upload) {
                    $this->em->remove($upload);
                }
                // dump($thumb);
                // dd($upload);
            }
            $s->setThumbnailJpeg(null);
            $progressBar->advance();
        }
        // dd('ok');
        $this->em->flush();    

        // SQlite will not downsize with simple delete,
        // so need to use sqlite vacuum command to clean inDb thumb spaces :
        // (will use twice db size...)
        if (str_starts_with($_SERVER['DATABASE_URL'] ?? '', 'sqlite://')) {
            // https://stackoverflow.com/questions/2143800/change-sqlite-file-size-after-delete-from-table
            // https://stackoverflow.com/questions/48894037/symfony4-sqlite3-connection
            $stmt = $this->em->getConnection()->prepare("VACUUM");
            // $stmt->execute();
            $stmt->executeStatement();
            // dd("VACUUM OK");
        }


        $progressBar->finish();
        $output->writeln(''); // To ensure line return after progress bar

        $this->em->flush();    

        $output->writeln([
            "<info>Did clean all timing tags OK</info>",
        ]);

        return $cmdStatus;
    }
}
