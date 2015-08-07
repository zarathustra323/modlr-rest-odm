<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Debug\Debug;

$env = getenv('ODM_REST_ENV') ?: 'dev';

// Require the vendor autoload file. @todo Should a bootstrap.php.cache file be used?
require_once __DIR__.'/../vendor/autoload.php';

// Create the master request
$request = Request::createFromGlobals();

$debug = false;
if ('dev' === $env) {
    $debug = true;
    Debug::enable();
}

require_once __DIR__.'/../app/AppKernel.php';

// Load the Kernel
$kernel = new AppKernel($env, $debug);
if ('prod' === $env) {
    $kernel->loadClassCache();
}

var_dump($kernel);

echo $env;
