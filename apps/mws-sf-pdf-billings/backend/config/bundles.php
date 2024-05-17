<?php
// TODO : not so good to edit bundles.php (will be rewrite on each composer new package installs...)
$bundles = [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Lexik\Bundle\JWTAuthenticationBundle\LexikJWTAuthenticationBundle::class => ['all' => true],
    Qipsius\TCPDFBundle\QipsiusTCPDFBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Twig\Extra\TwigExtraBundle\TwigExtraBundle::class => ['all' => true],
    Endroid\QrCodeBundle\EndroidQrCodeBundle::class => ['all' => true],
    Sensio\Bundle\FrameworkExtraBundle\SensioFrameworkExtraBundle::class => ['all' => true],
    Symfony\Bundle\MonologBundle\MonologBundle::class => ['all' => true],
    MWS\PDFBillingsLvl1Bundle\PDFBillingsLvl1Bundle::class => ['all' => true],
    Symfony\WebpackEncoreBundle\WebpackEncoreBundle::class => ['all' => true],
    MWS\MoonManagerBundle\MoonManagerBundle::class => ['all' => true],
    Knp\Bundle\PaginatorBundle\KnpPaginatorBundle::class => ['all' => true],
    FOS\JsRoutingBundle\FOSJsRoutingBundle::class => ['all' => true],
    Symfony\UX\StimulusBundle\StimulusBundle::class => ['all' => true],
    Symfony\UX\Svelte\SvelteBundle::class => ['all' => true],
    Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle::class => ['all' => true],
    Vich\UploaderBundle\VichUploaderBundle::class => ['all' => true],
    Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle::class => ['dev' => true, 'test' => true],
    Tchoulom\ViewCounterBundle\TchoulomViewCounterBundle::class => ['all' => true],
];

// Paid or private starter might not be loaded, do lazy loads :
if (class_exists(MWS\PDFBillingsMonwooBundle\PDFBillingsMonwooBundle::class)) {
    $bundles = array_merge($bundles, [
        MWS\PDFBillingsMonwooBundle\PDFBillingsMonwooBundle::class => ['all' => true],
    ]);
}
if (class_exists(MWS\PDFBillingsLvl2Bundle\PDFBillingsLvl2Bundle::class)) {
    $bundles = array_merge($bundles, [
        MWS\PDFBillingsLvl2Bundle\PDFBillingsLvl2Bundle::class => ['all' => true],
    ]);
}
// https://stackoverflow.com/questions/52151783/symfony-4-get-env-parameter-from-a-controller-is-it-possible-and-how
// dd($_SERVER['HAVE_MWS_DEMO']);
// dd(json_decode($_SERVER['HAVE_MWS_DEMO'] ?? 'false'));
if (class_exists(MWS\DemoBundle\DemoBundle::class)
&& json_decode($_SERVER['HAVE_MWS_DEMO'] ?? 'false')
) {
    $bundles = array_merge($bundles, [
        MWS\DemoBundle\DemoBundle::class => ['all' => true],
    ]);
}

if (class_exists(MWS\GooglePhotoReaderBundle\GooglePhotoReaderBundle::class)) {
    // var_dump('GooglePhotoReaderBundle ok'); exit;
    $bundles = array_merge($bundles, [
        MWS\GooglePhotoReaderBundle\GooglePhotoReaderBundle::class => ['all' => true],
    ]);
}

// In case of pre-production debugs, we want to get some messages, but still stay closest to the 
// production context. With that, we can debug without sending the dev debug bundle in production too ;)
if (class_exists(Symfony\Bundle\DebugBundle\DebugBundle::class)) {
    $bundles = array_merge($bundles, [
        Symfony\Bundle\DebugBundle\DebugBundle::class => ['dev' => true],
        Symfony\Bundle\MakerBundle\MakerBundle::class => ['dev' => true],
        Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => ['dev' => true, 'test' => true],
    ]);
    // dd($_ENV);
}

return $bundles;