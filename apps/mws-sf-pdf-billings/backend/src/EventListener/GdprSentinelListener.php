<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

// Listener registered from config/services.yaml as :
// services:App\EventListener\GdprSentinelListener:tags:[kernel.event_listener]

namespace App\EventListener;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\Event;

class GdprSentinelListener
{
  // TODO : buggy if going over 24 hours... of for now, demo will reset every 23.5 hours
  // protected $maxGdprCleanupSafeDelayInHr = 24;
  protected $maxGdprCleanupSafeDelayInHr = 23.5;

  protected LoggerInterface $logger;
  protected string $projectDir;
  protected \Twig\Environment $twig;

  public function __construct(
    LoggerInterface $logger,
    string $projectDir,
    \Twig\Environment $twig
	) {
    // echo "GDPRSentinelListener constructor OK"; exit;
    $this->logger = $logger;
    $this->projectDir = $projectDir;
    // https://stackoverflow.com/questions/20349194/how-to-send-var-to-view-from-event-listener-in-symfony2
    $this->twig = $twig;
    $this->twig->addGlobal('gdprSentinelLoaded', true);
  }

  // https://stackoverflow.com/questions/41839970/why-listener-setting-in-service-yml-in-symfony-not-working
  // https://symfony.com/doc/current/components/event_dispatcher.html#creating-and-dispatching-an-event
  // => using 'ExceptionEvent $event' is same as 'tags: - { name: kernel.event_listener, event: kernel.exception }'
  public function __invoke(
    // ExceptionEvent $event
    Event $event
  ): void {
    // https://www.w3docs.com/snippets/php/php-how-can-i-get-file-creation-date.html
    // echo "GDPRSentinelListener enabled OK"; exit; // TODO : DO e2e TESTS ENSURING gdpr is activated ?
    // Config value available for db start time == GDPRSentinelListener ok since will inject it ?
    $this->twig->addGlobal('gdprSentinelRequestCheck', true);

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
    // TODO : generic process for doctrine ORM instead of going at lower db implementation ? (dependent of sqlite techno for now)
    $database = $this->projectDir . '/var/data.db.sqlite';

    if (file_exists($safeGdprDatabase)) {
      $file_creation_date = filectime($safeGdprDatabase);
      $this->logger->debug(
        "Gdpr safe database created at : " . date('Y-m-d H:i:s', $file_creation_date)
      );
    } else {
      $this->logger->error(
        "Gdpr safe database missing at path : " . $safeGdprDatabase
      );
      return; // Do not messup working database, we did send some GDPR log error already...
    }

    // $database already exist otherwise whould have already give some sql error ?
    $database_creation_timestamp = filectime($database);
    $last_clean_date = new \DateTime();
    $last_clean_date->setTimestamp($database_creation_timestamp);

    $next_clean_date = clone $last_clean_date;
    $next_clean_date->add(
      // TIPS : will only work with INT hours
      // new \DateInterval('PT' . $this->maxGdprCleanupSafeDelayInHr . 'H')
      // TIPS : all float hours up to minutes precisions :
      new \DateInterval(
        'P' # Date parts
        // TODO : can't check times over 24hr ? need 1 day ?
        . intval($this->maxGdprCleanupSafeDelayInHr / 24) . 'D'
        . 'T' # Time parts
        . intval($this->maxGdprCleanupSafeDelayInHr) . 'H'
        . intval($this->maxGdprCleanupSafeDelayInHr * 60) % 60 . 'M'
      )
    );

    $this->twig->addGlobal('gdprLastCleanDate', $last_clean_date);
    $this->twig->addGlobal('gdprNextCleanDate', $next_clean_date);

    // $nextDiff = $next_clean_date->diff(new \DateTime());
    $nextDiff = (new \DateTime())->diff($next_clean_date);
    // var_dump($nextDiff);
    // TIPS : $nextDiff->i IS MINUTES, whereas $nextDiff->m is MONTH...
    $deltaHr = ($nextDiff->days * 24 + $nextDiff->h + $nextDiff->i/60)
    * ($nextDiff->invert ? -1 : 1);
    $this->logger->debug(
      "Gdpr database created at : " . $last_clean_date->format('Y-m-d H:i:s')
      . ". In usage up to : " . $next_clean_date->format('c') . ", same as "
      . $deltaHr . " hours remainings"
    );

    if ($deltaHr < 0) {
      $dbBackup = $this->projectDir . '/var/data.db.'
      . date('Ymd_His') . '.bckup.sqlite';
      // we clean up database every 24hr... (// TODO : send cleaning comming up live notification
      // 3 hours before to all user still connected before the forseen cleanup ?)
      copy($database, $dbBackup);
      $this->logger->info(
        "Gdpr database is too old, replacing by last available gdpr DB. "
        ."Backuped at : $dbBackup" 
      );
      unlink($database);
      copy($safeGdprDatabase, $database);

      // TODO : UI Feedback when cleaning ? it say
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
