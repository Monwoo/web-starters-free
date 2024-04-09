<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class FactoryResetController extends AbstractController
{
    #[Route(
        '/factory/reset/{forceTimeout}',
        defaults: [
            'forceTimeout' => false,
        ],
        name: 'app_factory_reset'
    )]
    public function index(bool $forceTimeout, string $projectDir, LoggerInterface $logger): JsonResponse
    {
        $msg = '';
        $didFail = false;
        // TODO : remove code duplication with apps/mws-sf-pdf-billings/backend/src/EventListener/GdprSentinelListener.php
        $database = $projectDir . '/var/data.db.sqlite';
        $safeGdprDatabase = $projectDir . '/var/data.gdpr-ok.db.sqlite';
        $dbBackup = $projectDir . '/var/data.db.' . date('Ymd_His') . '.bckup.sqlite';

        if (!file_exists($database)) {
            $logger->error(
                "FactoryResetController missing database path at : " . $database
            );
            $msg .= 'ERROR : Missing datababse file. ';
            copy($safeGdprDatabase, $database);
            $msg .= 'Resolution in progress : Did create database. ';
        }

        $clean = function () use (
            $msg, $database, $safeGdprDatabase, $dbBackup
        ) {
            copy($database, $dbBackup);
            unlink($database);
            copy($safeGdprDatabase, $database);
            $msg .= 'OK : Did resset this application to factory settings. ';
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
            $minDelay = 60; // attente minimum de 1 minute avant reset
            $next_possible_reset_date->setTimestamp($lastTimestamp ?? $timestamp);
            $next_possible_reset_date->add(new \DateInterval('PT1M'));

            $lastBckupDelta = $lastTimestamp
            ? $timestamp - $lastTimestamp
            : $minDelay;
            if ($lastBckupDelta < $minDelay) {
                $didFail = true;
                // TODO : e2e tests on user messages realistic with timings ? (using time stubs etc...)
                $msg .= 'ERROR : Please wait at most 1 minutes before next factory reset is available. ';
            } else {
                $clean();
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

        return $this->json([
            'isOK' => !$didFail,
            'message' => $msg,
            'server-clock' => $serverClock,
            'next-possible-reset' => $next_possible_reset_date,
        ]);
    }
}
