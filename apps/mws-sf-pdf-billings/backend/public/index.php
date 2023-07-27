<?php

use App\Kernel;

// https://stackoverflow.com/questions/30404121/tcpdf-serializetcpdftagparameters
define('K_TCPDF_CALLS_IN_HTML', true); // TOO late, already defined...

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

// https://stackoverflow.com/questions/66224775/cors-error-what-is-the-correct-way-to-configure-symfony-5-to-accept-cors
// https://stackoverflow.com/questions/44479681/cors-php-response-to-preflight-request-doesnt-pass-am-allowing-origin
// TODO : quick hacky cors, better to use bundles or some security bundle middeleware
// if ($_SERVER['APP_DEBUG']) {
//     header('Access-Control-Allow-Origin: '.rtrim($_SERVER['HTTP_REFERER'], '/'));
// } else {
//     header('Access-Control-Allow-Origin: *');
// }
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
    header("Access-Control-Allow-Headers: Authorization, Content-Type,Accept, Origin");
    exit(0);
}
header('Access-Control-Allow-Headers: *');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Headers: X-Requested-With, Content-Type, withCredentials');

return function (array $context) {
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};
