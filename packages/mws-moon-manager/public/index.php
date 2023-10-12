<?php
# TIPS : not used, but could be for some debug
# or bundle specific dev backend (or standalone app from bundle ?)
use MWS\MoonManagerBundle\Kernel;

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
