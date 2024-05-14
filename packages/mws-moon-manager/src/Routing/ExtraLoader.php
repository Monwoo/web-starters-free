<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖,
// build by Miguel Monwoo, service@monwoo.com

// https://symfony.com/doc/current/routing/custom_route_loader.html#creating-a-custom-loader
namespace MWS\MoonManagerBundle\Routing;

use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class ExtraLoader extends Loader
{
    private bool $isLoaded = false;

    public function load($resource, ?string $type = null): RouteCollection
    {
        // dd('ok');
        if (true === $this->isLoaded) {
            throw new \RuntimeException('Do not add the "extra" loader twice');
        }

        // TODO : load from YAML ?

        $routes = new RouteCollection();
        // TIPS : only fallback for missing other optional modules routes

        // // prepare a new route
        // $path = '/{_locale<%app.supported_locales%>}/mws-g-photo';
        // // $path = '/{_locale}/mws-g-photo';
        // $defaults = [
        //     '_controller' => 'MWS\GooglePhotoReaderBundle\Controller::index',
        // ];
        // $requirements = [
        //     // '_locale' => '%app.supported_locales%'
        // ];
        // $route = new Route($path, $defaults, $requirements);
        // // add the new route to the route collection
        // $routeName = 'mws_g_photo_reader_list';
        // $routes->add($routeName, $route);
        $this->isLoaded = true;

        return $routes;
    }

    public function supports($resource, ?string $type = null): bool
    {
        // dd('ok');
        return 'mws_extra' === $type;
    }
}