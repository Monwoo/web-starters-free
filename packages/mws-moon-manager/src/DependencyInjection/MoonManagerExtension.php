<?php
// 🌖🌖 Copyright Monwoo 2023 🌖🌖, build by Miguel Monwoo, service@monwoo.com

namespace MWS\MoonManagerBundle\DependencyInjection;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

/**
 * MoonManagerExtension
 */
class MoonManagerExtension extends Extension implements PrependExtensionInterface
{
    public function load(array $configs, ContainerBuilder $container): void
    {
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
        $loader->load('services.yaml');
        // dd($configs);
        // https://stackoverflow.com/questions/72350032/extend-security-configuration-from-symfony-bundle
        // You are not allowed to define new elements for path "security.firewalls". Please define all elements for this path in one config file."
        // https://symfonycasts.com/screencast/symfony5-upgrade/final-recipe-updates
        // TODO : write recipe instead ?
        // $loader->load('packages/security.yaml');
    }

    // https://stackoverflow.com/questions/50605939/override-bundle-template-from-another-bundle-in-symfony-4-5
    public function prepend(ContainerBuilder $container): void
    {
        // I recommend using FileLocator here 
        $thirdPartyBundlesViewFileLocator = (new FileLocator(__DIR__ . '/../../templates/bundles'));

        // dd($thirdPartyBundlesViewFileLocator->locate('TwigBundle'));
        $container->loadFromExtension('twig', [
            'paths' => [
                $thirdPartyBundlesViewFileLocator->locate('TwigBundle') => 'Twig',
            ],
        ]);
    }
}
