<?php


require "vendor/autoload.php";

$di = new Mushroom\Application(__DIR__);
$di->set('process', \DI\create(\Mushroom\Core\Process::class));
$di->set('request', \DI\create(\Mushroom\Core\Request::class));
$di->set(\Mushroom\Core\Console\Kernel::class, \DI\create(App\Console\Kernel::class));
$di->set(\Mushroom\Core\Route::class, \DI\create(\Mushroom\Core\Route::class));
$manage = $di->make(\Mushroom\Core\Console\Manage::class);
$manage->start();