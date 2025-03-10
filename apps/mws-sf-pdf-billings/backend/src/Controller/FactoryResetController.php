<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Annotation\Route;

class FactoryResetController extends AbstractController
{
    public function __construct(
        protected KernelInterface $kernel,
        protected ParameterBagInterface $params,
        protected LoggerInterface $logger,
    ) { }

    #[Route(
        '/factory/reset/{forceTimeout}',
        defaults: [
            'forceTimeout' => false,
        ],
        name: 'app_factory_reset',
        options: ['expose' => true],
    )]
    public function index(
        string $projectDir,
        LoggerInterface $logger,
        Request $request,
        bool $forceTimeout = false,
    ): JsonResponse|RedirectResponse {
        $msg = '';
        $didFail = false;
        // TODO : remove code duplication with apps/mws-sf-pdf-billings/backend/src/EventListener/GdprSentinelListener.php
        $database = $projectDir . '/var/data.db.sqlite';
        $safeGdprDatabase = $projectDir . '/var/data.gdpr-ok.db.sqlite';
        $safeGdprUploads = $projectDir . '/var/uploads-gdpr-ok';
        // $dbBackup = $projectDir . '/var/data.db.' . date('Ymd_His') . '.bckup.sqlite';

        // TODO : factorize with backup commande
        $filesystem = new Filesystem();

        if (!$filesystem->exists($database)) {
            $logger->error(
                "FactoryResetController missing database path at : " . $database
            );
            $msg .= 'ERROR : Missing datababse file. ';
            $filesystem->copy($safeGdprDatabase, $database);
            $msg .= 'Resolution in progress : Did create database. ';
        }

        $clean = function () use (
            $msg,
            $database,
            $safeGdprDatabase,
            $filesystem,
            $safeGdprUploads,
        ) {
            // Backup if configured to backup on each factory reset (for possible important data to keep even if should not be public for GDPR purpose)
            if (json_decode($_SERVER['FACTORY_RESET_DO_BACKUP'] ?? 'true')) {
                // copy($database, $dbBackup);
                $this->doBackup();
            }
            $projectDir = $this->params->get('kernel.project_dir');
            $subFolder = $this->getParameter('mws_moon_manager.uploadSubFolder') ?? '';
            $uploadSrc = "$projectDir/$subFolder";
            // unlink($database);
            $filesystem->remove([
                $database,
                $uploadSrc,
            ]);
            if (file_exists($safeGdprDatabase)) {
                $filesystem->copy($safeGdprDatabase, $database, true);
            } else {
                $msg .= "FAIL : missing $safeGdprDatabase.";
                $this->logger->warning("FAIL : missing $safeGdprDatabase.");
            }
            if (file_exists($safeGdprUploads)) {
                // $finder = new Finder();
                // // Avoid copy of db :
                // $finder->directories()->in($safeGdprUploads)
                //     ->ignoreDotFiles(true)
                //     ->ignoreUnreadableDirs()
                //     ->depth(0);
                // foreach ($finder as $f) {
                //     $p = $f->getPathname();
                //     $filesystem->copy($p, "$uploadSrc/$p", true);
                // }
                $filesystem->mirror($safeGdprUploads, $uploadSrc, null, [
                    'override' => true,
                    'delete' => true,
                ]);
            } else {
                $msg .= "FAIL : missing $safeGdprUploads.";
                $this->logger->debug("FAIL : missing $safeGdprUploads.");
            }
            $msg .= 'OK : Did reset this application to factory settings. ';
        };
        $serverClock = new \DateTime();
        $next_possible_reset_date = new \DateTime();
        // TODO : add param config on allowed IP for production ENV ?
        if ($forceTimeout && 'dev' === getenv('APP_ENV')) {
            $clean();
        } else {
            // TODO : timestamp file is better, any db write will postpone timer with current way :
            // https://stackoverflow.com/questions/13386082/filemtime-warning-stat-failed-for
            // $db_creation_timestamp = filectime(realpath($database)); TIPS : not accruate, will update on any DB operation...
            $timestamp = time();
            $lastTimestampFile = "$projectDir/var/factory-reset-timestamp.txt";
            $lastTimestamp = null;
            if (file_exists($lastTimestampFile)) {
                $lastTimestamp = intval(file_get_contents($lastTimestampFile));
            }
            $minDelay = round(json_decode($_SERVER['FACTORY_RESET_DELAY'] ?? '60')); // attente minimum de 1 minute avant reset
            $next_possible_reset_date->setTimestamp($lastTimestamp ?? $timestamp);
            // $next_possible_reset_date->add(new \DateInterval('PT1M'));
            $next_possible_reset_date->add(new \DateInterval("PT{$minDelay}S"));

            $lastBckupDelta = $lastTimestamp
                ? $timestamp - $lastTimestamp
                : $minDelay;
            if ($lastBckupDelta < $minDelay) {
                $didFail = true;
                $delta = $minDelay - $lastBckupDelta;
                // TODO : e2e tests on user messages realistic with timings ? (using time stubs etc...)
                $msg .= "ERROR : Please wait at most $delta secondes before next factory reset is available.";
                $logger->warning($msg);
            } else {
                $clean();
                // dd($timestamp);
                file_put_contents($lastTimestampFile, $timestamp);
            }
            // $stat = stat($database);
            // $db_creation_timestamp = $stat['ctime']; // date_create(date("Y-m-d", $stat['ctime']));

            // $next_possible_reset_date->setTimestamp($db_creation_timestamp);
            // // TIPS : allow factory reset only each 1 minutes to avoid service cleanups overloads
            // $next_possible_reset_date->add(new \DateInterval('PT1M'));
            // if (
            //     ($serverClock)->getTimestamp()
            //     > $next_possible_reset_date->getTimestamp()
            // ) {
            //     $clean();
            // } else {
            //     $didFail = true;
            //     // TODO : e2e tests on user messages realistic with timings ? (using time stubs etc...)
            //     $msg .= 'ERROR : Please wait at most 5 minutes before next factory reset is available. ';
            // }
        }

        // TODO : clean upload folders too ?
        if ($request->isXmlHttpRequest() || $didFail) {
            return $this->json([
                'isOK' => !$didFail,
                'message' => $msg,
                'server-clock' => $serverClock,
                'next-possible-reset' => $next_possible_reset_date,
            ]);
        }
        return $this->redirectToRoute(
            'app_home'
        );
    }

    protected function doBackup()
    {
        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        $input = new ArrayInput([
            'command' => 'mws:backup',
        ]);
        $output = new NullOutput();
        $application->run($input, $output);
    }
}
