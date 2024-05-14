<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Routing\RouteCollection;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    // private function configureRoutes(RoutingConfigurator $routes): void
    // {
    //     // parent::configureRoutes($routes);
    //     // TODO : better overwrite ? : since hard to call private trait method ? infinit loop or wrong class error :
    //     // public function loadRoutes(LoaderInterface $loader): RouteCollection

    //     // $configureRoutes = new \ReflectionMethod(MicroKernelTrait::class, 'configureRoutes');
    //     $configureRoutes = new \ReflectionMethod(Kernel::class, 'configureRoutes'); // Infinit loop
    //     $configureRoutes->setAccessible(true);
    //     // MicroKernelTrait::configureRoutes($routes);
    //     // $configureRoutes->getClosure($this)($routes);
    //     // $configureRoutes->invoke($this)($routes);
    //     $configureRoutes->invoke($this)($routes);

    //     if (class_exists(MWS\GooglePhotoReaderBundle\GooglePhotoReaderBundle::class)) {
    //         // https://stackoverflow.com/questions/14679377/get-the-path-of-a-directory-inside-a-bundle-in-symfony
    //         // FileLocator $fileLocator
    //         // $resourcePath = $this->fileLocator->locate('@AppBundle/Resources/some_resource');
    //         $gPhotoBundleRoutes = $this->locateResource(
    //             '@GooglePhotoReaderBundle/config/routes/mws_google_photo_reader.yaml'
    //         );
    //         $routes->import($gPhotoBundleRoutes);
    //     }
    // }

    /**
     * @internal
     */
    public function loadRoutes(LoaderInterface $loader): RouteCollection
    {
        $file = (new \ReflectionObject($this))->getFileName();
        /* @var RoutingPhpFileLoader $kernelLoader */
        $kernelLoader = $loader->getResolver()->resolve($file, 'php');
        $kernelLoader->setCurrentDir(\dirname($file));
        $collection = new RouteCollection();

        $configureRoutes = new \ReflectionMethod($this, 'configureRoutes');
        $routeConfigurator = new RoutingConfigurator($collection, $kernelLoader, $file, $file, $this->getEnvironment());
        $configureRoutes->getClosure($this)($routeConfigurator);
        if (class_exists(\MWS\GooglePhotoReaderBundle\GooglePhotoReaderBundle::class)) {
            // dd('ok');
            // https://stackoverflow.com/questions/14679377/get-the-path-of-a-directory-inside-a-bundle-in-symfony
            // FileLocator $fileLocator
            // $resourcePath = $this->fileLocator->locate('@AppBundle/Resources/some_resource');
            $gPhotoBundleRoutes = $this->locateResource(
                '@GooglePhotoReaderBundle/config/routes/mws_google_photo_reader.yaml'
            );
            $routeConfigurator->import($gPhotoBundleRoutes);
        }

        foreach ($collection as $route) {
            $controller = $route->getDefault('_controller');

            if (\is_array($controller) && [0, 1] === array_keys($controller) && $this === $controller[0]) {
                $route->setDefault('_controller', ['kernel', $controller[1]]);
            } elseif ($controller instanceof \Closure && $this === ($r = new \ReflectionFunction($controller))->getClosureThis() && !str_contains($r->name, '{closure}')) {
                $route->setDefault('_controller', ['kernel', $r->name]);
            }
        }

        return $collection;
    }

}
