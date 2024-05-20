<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

// Listener registered from config/services.yaml as :
// services:App\EventListener\GdprSentinelListener:tags:[kernel.event_listener]

namespace App\EventListener;

use App\Controller\FactoryResetController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\Event;

// TODO : class GdprSentinelListener implements EventSubscriberInterface ? hard service linked for now
class GdprSentinelListener
{
  // TODO : buggy if going over 24 hours... of for now, demo will reset every 23.5 hours
  // protected $maxGdprCleanupSafeDelayInHr = 24;
  protected $maxGdprCleanupSafeDelayInHr = 23.5;

  protected LoggerInterface $logger;
  protected string $projectDir;
  protected \Twig\Environment $twig;
  protected Request $request;

  public function __construct(
    // protected Request $request, // Not allowed for event_listener
    protected KernelInterface $kernel,
    protected UrlGeneratorInterface $urlGenerator,
    LoggerInterface $logger,
    string $projectDir,
    \Twig\Environment $twig
  ) {
    // echo "GDPRSentinelListener constructor OK"; exit;
    $this->logger = $logger;
    $this->projectDir = $projectDir;
    // https://stackoverflow.com/questions/20349194/how-to-send-var-to-view-from-event-listener-in-symfony2
    $this->twig = $twig;
    // $this->twig->addGlobal('gdprSentinelLoaded', true);
  }

  // https://stackoverflow.com/questions/41839970/why-listener-setting-in-service-yml-in-symfony-not-working
  // https://symfony.com/doc/current/components/event_dispatcher.html#creating-and-dispatching-an-event
  // => using 'ExceptionEvent $event' is same as 'tags: - { name: kernel.event_listener, event: kernel.exception }'
  public function __invoke(
    // ExceptionEvent $event
    // Event $event
    ResponseEvent|ExceptionEvent|RequestEvent $event
  ): void {
    // $response = $event->getResponse();
    // $request = $event->getRequest();
    // if ($event->getResponse())
    $this->request = $event->getRequest();

    // https://www.w3docs.com/snippets/php/php-how-can-i-get-file-creation-date.html
    // echo "GDPRSentinelListener enabled OK"; exit; // TODO : DO e2e TESTS ENSURING gdpr is activated ?
    // Config value available for db start time == GDPRSentinelListener ok since will inject it ?
    $GDPR_SENTINEL_REQUEST_CHECK = json_decode($_SERVER['GDPR_SENTINEL_REQUEST_CHECK'] ?? 'null') ?? true;
    $this->twig->addGlobal('gdprSentinelRequestCheck', $GDPR_SENTINEL_REQUEST_CHECK);
    // dd($GDPR_SENTINEL_REQUEST_CHECK);

    if (!$GDPR_SENTINEL_REQUEST_CHECK) {
      return;
    }

    if (!$event->isMainRequest()) {
      // TIPS : Ignore SubRequest to avoid infinite loop on next sub request...
      return;
    }

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
      // // https://stackoverflow.com/questions/13386082/filemtime-warning-stat-failed-for
      $file_update_date = filectime(realpath($safeGdprDatabase));
      // // $stat = stat($safeGdprDatabase);
      // $file_update_date = $stat['ctime']; // date_create(date("Y-m-d", $stat['ctime']));

      $this->logger->debug(
        "Gdpr safe database last update at : " . date('Y-m-d H:i:s', $file_update_date)
      );
    } else {
      $this->logger->error(
        "Gdpr safe database missing at path : " . $safeGdprDatabase
      );
      return; // Do not messup working database, we did send some GDPR log error already...
    }

    // // $database already exist otherwise whould have already give some sql error ?
    // // https://stackoverflow.com/questions/13386082/filemtime-warning-stat-failed-for
    // $database_creation_timestamp = filectime(realpath($database));
    // // $stat = stat(realpath($database)); // Will be false on next call, normal ?
    // // $database_creation_timestamp = $stat['ctime']; // date_create(date("Y-m-d", $stat['ctime']));
    // $last_clean_date = new \DateTime();
    // $last_clean_date->setTimestamp($database_creation_timestamp);
    // $next_clean_date = clone $last_clean_date;
    // $next_clean_date->add(
    //   // TIPS : will only work with INT hours
    //   // new \DateInterval('PT' . $this->maxGdprCleanupSafeDelayInHr . 'H')
    //   // TIPS : all float hours up to minutes precisions :
    //   new \DateInterval(
    //     'P'
    //     # Date parts
    //     // TODO : can't check times over 24hr ? need 1 day ? Day counts on 25hr is 2 => upper floor ?
    //     . intval($this->maxGdprCleanupSafeDelayInHr / 24) . 'D'
    //     . 'T' # Time parts
    //     . intval($this->maxGdprCleanupSafeDelayInHr) . 'H'
    //     . intval($this->maxGdprCleanupSafeDelayInHr * 60) % 60 . 'M'
    //   )
    // );
    $timestamp = time();
    $lastTimestampFile = "{$this->projectDir}/var/factory-reset-timestamp.txt";
    $lastTimestamp = null;
    if (file_exists($lastTimestampFile)) {
      $lastTimestamp = intval(file_get_contents($lastTimestampFile));
    }
    $minDelay = json_decode($_SERVER['GDPR_RESET_DELAY'] ?? '72'); // attente minimum de 72 hr avant reset GDPR
    $last_clean_date = new \DateTime();
    $last_clean_date->setTimestamp($lastTimestamp ?? $timestamp);
    $next_clean_date = clone $last_clean_date;
    // $next_clean_date->add(new \DateInterval('PT1M'));
    $minDelaySec = round($minDelay * 60 * 60);
    // dd("PT{$minDelaySec}S");
    $next_clean_date->add(new \DateInterval("PT{$minDelaySec}S"));
    // dump($last_clean_date);
    // dd($next_clean_date);

    $lastBckupDelta = $lastTimestamp
      ? $timestamp - $lastTimestamp
      : $minDelaySec;

    $this->twig->addGlobal('gdprLastCleanDate', $last_clean_date);
    $this->twig->addGlobal('gdprNextCleanDate', $next_clean_date);

    // dd($this->request->headers);
    // $this->request->headers->has('forwarded')

    if (
      $this->request->headers->has('x-mws-redirect-after-gdpr-reset')
    ) {
      return;
    }

    // dd($minDelay);
    // dd($lastBckupDelta);
    if ($lastBckupDelta >= $minDelaySec) {
      // $didFail = true;
      // // TODO : e2e tests on user messages realistic with timings ? (using time stubs etc...)
      // $msg .= 'ERROR : Please wait at most 1 minutes before next factory reset is available. ';
      $attr = [
        '_controller' => FactoryResetController::class . '::index',
      ];
      $params = [];
      $subRequest = $this->request->duplicate($params, null, $attr);

      /** @var Response $resp */
      $resp = $this->kernel
        ->handle($subRequest, HttpKernelInterface::SUB_REQUEST);
      $data = $resp->getContent();
      // dd($resp);
      if (404 === $resp->getStatusCode()) {
        // $data = null;
        // If fail, no pb, will retry on next refresh...
      } elseif (302 === $resp->getStatusCode()) {
        // TIPS : will redirect on success if not json Api request...
        // 302 === $resp->getStatusCode()
        // $data = $resp->getContent();
        $response = new RedirectResponse(
          $this->urlGenerator->generate('app_home')
        );
        $response->headers->set('X-mws-redirect-after-gdpr-reset', true);
        $event->setResponse($response);
      }
    } else {
      // // $nextDiff = $next_clean_date->diff(new \DateTime());
      // $nextDiff = (new \DateTime())->diff($next_clean_date);
      // // var_dump($nextDiff);
      // // TIPS : $nextDiff->i IS MINUTES, whereas $nextDiff->m is MONTH...
      // $deltaHr = ($nextDiff->days * 24 + $nextDiff->h + $nextDiff->i/60)
      // * ($nextDiff->invert ? -1 : 1);
      $this->logger->debug(
        "Gdpr database created at : " . $last_clean_date->format('Y-m-d H:i:s')
          . ". In usage up to : " . $next_clean_date->format('c')
        // . ", same as " . $deltaHr . " hours remainings"
      );

      // if ($deltaHr < 0) {
      //   $dbBackup = $this->projectDir . '/var/data.db.'
      //   . date('Ymd_His') . '.bckup.sqlite';
      //   // we clean up database every 24hr... (// TODO : send cleaning comming up live notification
      //   // 3 hours before to all user still connected before the forseen cleanup ?)
      //   if (file_exists($database)) {
      //     copy($database, $dbBackup);
      //     $this->logger->info(
      //       "Gdpr database is too old, replacing by last available gdpr DB. "
      //       ."Backuped at : $dbBackup" 
      //     );
      //     unlink($database);  
      //   }
      //   copy($safeGdprDatabase, $database);
      //   // TODO : UI Feedback when cleaning ? it say
      // }

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
