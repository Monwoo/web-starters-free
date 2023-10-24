<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use MWS\MoonManagerBundle\Entity\MwsOffer;

#[AsEntityListener(event: Events::postUpdate, method: 'postUpdate', entity: MwsOffer::class)]
class MwsOfferPostUpdate
{
  // https://symfony.com/doc/current/doctrine/events.html
  // the entity listener methods receive two arguments:
  // the entity instance and the lifecycle event
  public function postUpdate(MwsOffer $user, PostUpdateEventArgs $event): void
  {
    $entityManager = $event->getObjectManager();

    // Ensure tags category cleanups and/or unicity on categoryOkWithMultiplesTags ?
  }
}
