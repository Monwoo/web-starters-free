<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

// Listener registered from config/services.yaml as :
// services:App\EventListener\GdprSentinelListener:tags:[kernel.event_listener]

namespace App\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Psr\Log\LoggerInterface;

class GdprSentinelListener
{
  protected LoggerInterface $logger;
  protected string $projectDir;
	public function __construct(
    LoggerInterface $logger,
    string $projectDir		
	) {
    $this->logger = $logger;
    $this->projectDir = $projectDir;
  }

  public function __invoke(
    ExceptionEvent $event
  ): void {
    // https://www.w3docs.com/snippets/php/php-how-can-i-get-file-creation-date.html

    if (!$this->projectDir || !(strlen($this->projectDir) > 0)) {
      $this->logger->error(
        'GdprSentinelListener missing service config '
        . 'services:_defaults:bind:string $projectDir: "%kernel.project_dir%" ?'
        . ' at apps/mws-sf-pdf-billings/backend/config/services.yaml ?'
      );
      return; // Do not messup working database, we did send some GDPR log error already...
    }

    // Due to Gdpr, we do not want to keep possible sensitive informations
    // more than 72hr to be materially safe...
    // For our aim, empty database is ok, since we can add billings informations quickly
    // by HTML get form or POST request with CSRF
    $safeGdprDatabase = $this->projectDir . '/var/data.gdpr-ok.db.sqlite';
    $database = $this->projectDir . '/var/data.gdpr-ok.db.sqlite';

    if (file_exists($safeGdprDatabase)) {
      $file_creation_date = filectime($safeGdprDatabase);
      $this->logger->debug(
        "Gdpr safe databse created at : " . date('Y-m-d H:i:s', $file_creation_date)
      );
    } else {
      $this->logger->error(
        "Gdpr safe databse missing at path : " . $safeGdprDatabase
      );
      return; // Do not messup working database, we did send some GDPR log error already...
    }

    // $database already exist otherwise whould have already give some sql error ?
    $database_creation_timestamp = filectime($database);
    $database_creation_date = new \DateTime();
    $database_creation_date->setTimestamp($database_creation_timestamp);

    $deltaHr = abs($database_creation_date->diff(new \DateTime())->days);
    $this->logger->debug(
      "Gdpr databse created at : " . $database_creation_date->format('Y-m-d H:i:s')
      .". In usage from : " . $deltaHr . " hours"
    );

    if ($deltaHr > 24) {
      $dbBackup = $this->projectDir . '/var/data.db.'
      . date('Ymd_His') . '.bckup.sqlite';
      // we clean up database every 24hr... (// TODO : send cleaning comming up live notification
      // 3 hours before to all user still connected before the forseen cleanup ?)
      $this->logger->debug(
        "Gdpr database is too old, replacing by last available gdpr DB"
        ."Backuped at : "
      );
      copy($database, $dbBackup);
      unlink($database);
      copy($safeGdprDatabase, $database);
    }

    // TODO : show time counter before next forseen cleaning db time ? or do with
    //         db redux 'appState' model empty on 'lastGdprDateCheck'
    //         that get filled by this sentinel if empty ?

    // // https://symfony.com/doc/current/event_dispatcher.html
    // // You get the exception object from the received event
    // $exception = $event->getThrowable();
    // $message = sprintf(
    //   'My Error says: %s with code: %s',
    //   $exception->getMessage(),
    //   $exception->getCode()
    // );

    // // Customize your response object to display the exception details
    // $response = new Response();
    // $response->setContent($message);

    // // HttpExceptionInterface is a special type of exception that
    // // holds status code and header details
    // if ($exception instanceof HttpExceptionInterface) {
    //   $response->setStatusCode($exception->getStatusCode());
    //   $response->headers->replace($exception->getHeaders());
    // } else {
    //   $response->setStatusCode(Response::HTTP_INTERNAL_SERVER_ERROR);
    // }

    // // sends the modified response object to the event
    // $event->setResponse($response);
  }
}
