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
];

// Paid or private starter might not be loaded, do lazy loads :
if (class_exists(MWS\PDFBillingsMonwooBundle\PDFBillingsMonwooBundle::class)) {
    $bundles = array_merge($bundles, [
        MWS\PDFBillingsMonwooBundle\PDFBillingsMonwooBundle::class => ['all' => true],
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
}

return $bundles;