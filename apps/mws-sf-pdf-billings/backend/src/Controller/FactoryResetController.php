<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class FactoryResetController extends AbstractController
{
    #[Route('/factory/reset', name: 'app_factory_reset')]
    public function index(string $projectDir, LoggerInterface $logger): JsonResponse
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
        $serverClock = new \DateTime();
        $db_creation_timestamp = filectime($database);
        $next_possible_reset_date = new \DateTime();
        $next_possible_reset_date->setTimestamp($db_creation_timestamp);
        // TIPS : allow factory reset only each 5 minutes to avoid service cleanups overloads
        $next_possible_reset_date->add(new \DateInterval('PT5M'));
        if (
            ($serverClock)->getTimestamp()
            > $next_possible_reset_date->getTimestamp()
        ) {
            copy($database, $dbBackup);
            unlink($database);
            copy($safeGdprDatabase, $database);
            $msg .= 'OK : Did resset this application to factory settings. ';
        } else {
            $didFail = true;
            // TODO : e2e tests on user messages realistic with timings ? (using time stubs etc...)
            $msg .= 'ERROR : Please wait at most 5 minutes before next factory reset is available. ';
        }

        return $this->json([
            'isOK' => !$didFail,
            'message' => $msg,
            'server-clock' => $serverClock,
            'next-possible-reset' => $next_possible_reset_date,
        ]);
    }
}