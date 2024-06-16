<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

// Inspired from :
// https://medium.com/@galopintitouan/executing-database-migrations-at-scale-with-symfony-and-doctrine-4c60f86865b4

namespace MWS\MoonManagerBundle\EventSubscriber;

// use Psr\SimpleCache\CacheInterface;

use MWS\MoonManagerBundle\Command\MigrateDatabaseCommand;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
// use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

class MwsMaintenanceSubscriber implements EventSubscriberInterface
{
  private $cache;
  private $twig;

  public function __construct(CacheInterface $c, Environment $t)
  {
    $this->cache = $c;
    $this->twig = $t;
  }

  public static function getSubscribedEvents(): array
  {
    return [
      KernelEvents::REQUEST => [
        'enableMaintenanceOnRequest',
        1000000 // Always execute the listener as first (After GDPR sentinels etc...d)
      ],
    ];
  }

  public function enableMaintenanceOnRequest(RequestEvent $e)
  {
    // $this->cache->delete(MigrateDatabaseCommand::MAINTENANCE_KEY); // TODO : option to force lock release in case of buggy command missing release...
    $isM = $this->cache->get(MigrateDatabaseCommand::MAINTENANCE_KEY, function ($item) {
      return false;
    });
    // dd($isM);
    if (!$isM) {
      return;
    }

    $e->setResponse(new Response(
      $this->twig->render('@MoonManager/maintenance.html.twig'),
      Response::HTTP_SERVICE_UNAVAILABLE
    ));
  }
}
