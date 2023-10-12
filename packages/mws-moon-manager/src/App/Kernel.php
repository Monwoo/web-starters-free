<?php

// Mandatory to run console commands
// from inside bundle folder

// default generated file are with 'App' namespace
// namespace App;
// But our source directory is build with :
namespace MWS\MoonManagerBundle\App;


use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;

class Kernel extends BaseKernel
{
    use MicroKernelTrait;
}
