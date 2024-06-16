<?php
// 🌖🌖 Copyright Monwoo 2024 🌖🌖, build by Miguel Monwoo, service@monwoo.com

// Inspired from :
// https://stackoverflow.com/questions/46893809/autowire-symfony-cacheinterface-depending-on-environment
// https://symfony.com/doc/current/service_container/factories.html#non-static-factories
// https://symfony.com/doc/current/components/cache.html
namespace MWS\MoonManagerBundle\Services;

// use Psr\SimpleCache\CacheInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;

final class CacheFactory
{
  public function create(string $environment): CacheInterface
  {
    // TODO : efficient cache ? used for maintenance mode only for now... FS under SSD is ok...
    // if ($environment === 'prod') {
    //     // do this
    //     // https://symfony.com/doc/current/components/cache/adapters/memcached_adapter.html
    //     $client = MemcachedAdapter::createConnection(
    //       'memcached://localhost'
    //     );
    //     return new MemcachedAdapter($client); // or Redis server if you have, etc... 
    // }

    // default 
    // https://symfony.com/doc/current/components/cache/adapters/filesystem_adapter.html
    return new FilesystemAdapter();
  }
}
