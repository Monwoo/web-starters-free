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
use MWS\MoonManagerBundle\Services\MaxPriceTagManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'mws:recompute-timing-tags',
    description: 'Recompute timing tags Max based on loaded tags. ex : \n
        time php -d memory_limit=8G bin/console mws:recompute-timing-tags',
    hidden: false,
    // aliases: ['app:make-user']
)]
class RecomputeTimingTags extends Command
{
    public function __construct(
        protected ManagerRegistry $registry,
        protected EntityManagerInterface $em,
        protected bool $deleteAll = false,
    ) {
        // TIPS : best practices recommend to call the parent constructor first and
        // then set your own properties. That wouldn't work in this case
        // because configure() needs the properties set in this constructor
        // $this->requirePassword = $requirePassword;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'deleteAll', '-d', InputArgument::OPTIONAL,
            "Should delete ALL", $this->deleteAll
        );
    }


    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->deleteAll = $input->getOption("deleteAll");

        $cmdStatus = Command::SUCCESS;

        if ($this->deleteAll) {
            $qb = $this->em->createQueryBuilder()
            ->delete(MwsTimeSlot::class, 's');
            $resp = $qb->getQuery()->execute();
            $output->writeln([
                "<info>Did delete all tags OK</info>",
            ]);
        }

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

        /** @var MwsTimeSlot $s */
        foreach ($resp as $s) {
            // TODO : ok here or better using event system ? (strong design will use other design patterns..)
            [$maxTag, $maxPath] = MaxPriceTagManager::pickMaxOf(
                $s->getTags()->toArray()
            );
            $s->setMaxPriceTag($maxTag);
            $s->setMaxPath($maxPath);
            // $progressBar->setMessage($s->getSourceStamp());
            // $progressBar->setMessage('hello');
            // sleep(1);
            // dd($u);

            // TIPS : will be TOO slow if flushing to storage each time
            // doing bulk flush at end is lot more faster 
            // (you might need more memory to run with 'php -d memory_limit=8G' ):
            // $this->em->flush();

            $progressBar->advance();
        }
        $this->em->flush();    
        $progressBar->finish();
        $output->writeln(''); // To ensure line return after progress bar

        $this->em->flush();    

        $output->writeln([
            "<info>Did recompute all timing tags OK</info>",
        ]);

        return $cmdStatus;
    }
}
