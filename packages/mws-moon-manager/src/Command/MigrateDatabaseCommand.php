<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

// https://medium.com/@galopintitouan/executing-database-migrations-at-scale-with-symfony-and-doctrine-4c60f86865b4
// https://github.com/doctrine/DoctrinePHPCRBundle/tree/3.x ?

namespace MWS\MoonManagerBundle\Command;

use Doctrine\Bundle\MigrationsBundle\Command\DoctrineCommand;
use Doctrine\Bundle\MigrationsBundle\Command\Helper\DoctrineCommandHelper;
// use Psr\SimpleCache\CacheInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\Migrations\Tools\Console\Command\DoctrineCommand as CommandDoctrineCommand;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;

// class MigrateDatabaseCommand extends MigrateCommand
#[AsCommand(
  name: 'mws:db:migrate',
  // description: 'Will do some migrations.',
  hidden: false,
  // aliases: ['backup:sync']
)]
class MigrateDatabaseCommand extends CommandDoctrineCommand
{
  // Depreciated : (use annotations ok)
  // protected static $defaultName = 'mws:db:migrate';

  private $cache;

  public function __construct(CacheInterface $cache)
  {
    parent::__construct();

    $this->cache = $cache;
  }

  protected function configure(): void
  {
    parent::configure();

    $this
      ->setDescription('Check if a database migration is needed and enable maintenance mode to execute it if there is.')
      ->addOption('db', null, InputOption::VALUE_REQUIRED, 'The database connection to use for this command.')
      ->addOption('em', null, InputOption::VALUE_REQUIRED, 'The entity manager to use for this command.')
      ->addOption('shard', null, InputOption::VALUE_REQUIRED, 'The shard connection to use for this command.');
  }

  const MIGRATE_KEY = 'mws.migrationInProgress';
  // const MIGRATE_KEY = 'mws.maintenanceInProgress';
  const MAINTENANCE_KEY = 'mws.maintenanceInProgress'; // mws. prefix ?
  public function execute(InputInterface $input, OutputInterface $output)
  {
    $io = new SymfonyStyle($input, $output);

    // $productsCount = $this->cache->getItem(self::MIGRATE_KEY);
    // $this->cache->delete(self::MIGRATE_KEY); // TODO : option to force lock release in case of buggy command missing release...

    if ($this->cache->get(self::MIGRATE_KEY, function ($item) {
      return false;
    })) {
      $io->success('Another instance locked the migration');

      return Command::FAILURE;
    }

    // Take the responsibility to execute the migration
    $io->text('Locking');
    // $this->cache->set('migrating', true, 60);
    $this->cache->delete(self::MIGRATE_KEY);
    $this->cache->get(self::MIGRATE_KEY, function ($k) {
      return true;
    });

    $io->text('Lock obtained');

    // Check whether there are migrations to execute or not
    $io->text('Loading migrations');

    $app = $this->getApplication();
    // TODO : replacement for legacy ? or missing package ?
    // "Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand not found...
    DoctrineCommand::setApplicationHelper($app, $input);

    DoctrineCommand::configureMigrations(
      $app->getKernel()->getContainer(),
      $this->getMigrationConfiguration($input, $output)
    );

    $configuration = $this->getMigrationConfiguration($input, $output);
    $toExecute = array_diff(
      $configuration->getAvailableVersions(),
      $configuration->getMigratedVersions()
    );

    if (!$toExecute) {
      // No migration to execute: do not enable maintenance
      $io->success('No migration to execute');

      $io->text('Releasing lock');
      // $this->cache->delete('migrating');
      $this->cache->delete(self::MIGRATE_KEY);
      $io->text('Lock released');

      return Command::SUCCESS;
    }

    // Migrations to execute: enable maintenance and run them
    $io->text('Migration(s) to execute: ' . implode(', ', $toExecute));

    $io->text('Enabling maintenance mode');
    // $this->cache->set('maintenance', true, 60);
    $this->cache->delete(self::MAINTENANCE_KEY);
    $this->cache->get(self::MAINTENANCE_KEY, function ($k) {
      $k->set(true, 60);
      return true;
    });

    $io->text('Maintenance enabled');

    $io->text("Executing the migration(s)\n");

    // Enable full output and disable the migration question
    $output->setVerbosity(OutputInterface::VERBOSITY_DEBUG);
    $input->setOption('no-interaction', true);

    parent::execute($input, $output);

    $output->write("\n");
    $io->text('Migration(s) executed');

    $io->text('Disabling maintenance mode');
    $this->cache->delete(self::MAINTENANCE_KEY);
    $io->text('Maintenance disabled');

    $io->text('Releasing lock');
    $this->cache->delete(self::MIGRATE_KEY);
    $io->success('Lock released');
    return Command::SUCCESS;
  }
}
