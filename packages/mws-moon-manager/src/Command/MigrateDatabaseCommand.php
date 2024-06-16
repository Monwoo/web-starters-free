<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

// https://medium.com/@galopintitouan/executing-database-migrations-at-scale-with-symfony-and-doctrine-4c60f86865b4
// https://github.com/doctrine/DoctrinePHPCRBundle/tree/3.x ?

namespace MWS\MoonManagerBundle\Command;

use Doctrine\Bundle\MigrationsBundle\Command\Helper\DoctrineCommandHelper;
// use Psr\SimpleCache\CacheInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\DBAL\Migrations\Tools\Console\Command\MigrateCommand;
use Doctrine\Migrations\Exception\NoMigrationsFoundWithCriteria;
use Doctrine\Migrations\Exception\NoMigrationsToExecute;
use Doctrine\Migrations\Exception\UnknownMigrationVersion;
use Doctrine\Migrations\Tools\Console\Command\DoctrineCommand;
use Doctrine\Migrations\Tools\Console\ConsoleRunner;
use Doctrine\Migrations\Tools\Console\MigratorConfigurationFactory;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Style\SymfonyStyle;

// class MigrateDatabaseCommand extends MigrateCommand
#[AsCommand(
  name: 'mws:db:migrate',
  // description: 'Will do some migrations.',
  hidden: false,
  // aliases: ['backup:sync']
)]
class MigrateDatabaseCommand extends DoctrineCommand // MigrateCommand is final, use as doc...
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
    $this
      ->setDescription('Check if a database migration is needed and enable maintenance mode to execute it if there is.')
      ->addArgument(
        'version',
        InputArgument::OPTIONAL,
        'The version FQCN or alias (first, prev, next, latest) to migrate to.',
        'latest'
      )
      ->addOption('db', null, InputOption::VALUE_REQUIRED, 'The database connection to use for this command.')
      ->addOption('em', null, InputOption::VALUE_REQUIRED, 'The entity manager to use for this command.')
      ->addOption('shard', null, InputOption::VALUE_REQUIRED, 'The shard connection to use for this command.');
    parent::configure();
  }

  const MIGRATE_KEY = 'mws.migrationInProgress';
  // const MIGRATE_KEY = 'mws.maintenanceInProgress';
  const MAINTENANCE_KEY = 'mws.maintenanceInProgress'; // mws. prefix ?
  public function execute(InputInterface $input, OutputInterface $output): int
  {
    $io = new SymfonyStyle($input, $output);

    // $productsCount = $this->cache->getItem(self::MIGRATE_KEY);
    $this->cache->delete(self::MIGRATE_KEY); // TODO : option to force lock release in case of buggy command missing release...

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

    // $app = $this->getApplication();
    // // TODO : replacement for legacy ? or missing package ?
    // // "Doctrine\Bundle\DoctrineBundle\Command\DoctrineCommand not found...
    // CommandDoctrineCommand::setApplicationHelper($app, $input);
    // DoctrineCommand::configureMigrations(
    //   $app->getKernel()->getContainer(),
    //   $this->getMigrationConfiguration($input, $output)
    // );
    // $configuration = $this->getMigrationConfiguration($input, $output);
    // $toExecute = array_diff(
    //   $configuration->getAvailableVersions(),
    //   $configuration->getMigratedVersions()
    // );
    $versionAlias     = $input->getArgument('version');
    // TODO : not working here but ok in doctrine final class command ?
    // $df = ConsoleRunner::findDependencyFactory();
    $df = $this->getDependencyFactory();

    $migratorConfigurationFactory = $df->getConsoleInputMigratorConfigurationFactory();
    $migratorConfiguration        = $migratorConfigurationFactory->getMigratorConfiguration($input);
    $migratorConfiguration->isDryRun();
    $databaseName = (string) $df->getConnection()->getDatabase();
    $df->getMetadataStorage()->ensureInitialized();
    $migrationRepository = $df->getMigrationRepository();
    if (count($migrationRepository->getMigrations()) === 0) {
      $message = sprintf(
        'The version "%s" couldn\'t be reached, there are no registered migrations.',
        $versionAlias
      );
      $this->io->warning($message);
      return Command::FAILURE;
    }

    // https://vscode.dev/github/Monwoo/web-starters-free/blob/main/packages/mws-moon-manager/vendor/doctrine/migrations/lib/Doctrine/Migrations/Tools/Console/Command/MigrateCommand.php#L261
    try {
      $version = $df->getVersionAliasResolver()->resolveVersionAlias($versionAlias);
    } catch (UnknownMigrationVersion $e) {
      $this->io->error(sprintf(
        'Unknown version: %s',
        OutputFormatter::escape($versionAlias)
      ));
      // return 1;
    } catch (NoMigrationsToExecute | NoMigrationsFoundWithCriteria $e) {
      // return $this->exitForAlias($versionAlias);
    }

    $toExecute = false;
    if ($version) {
      $planCalculator = $df->getMigrationPlanCalculator();
      $plan = $planCalculator->getPlanUntilVersion($version);
      $toExecute = count($plan) === 0;
    }

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
