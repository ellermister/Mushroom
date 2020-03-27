<?php

require "vendor/autoload.php";

$di = new Mushroom\Application(__DIR__);
$di->set('process', \DI\create(\Mushroom\Core\Process::class));
$di->set('request', \DI\create(\Mushroom\Core\Request::class));
$di->set('websocket', \DI\create(\Mushroom\Core\Http\Websocket::class)->constructor(\DI\get(\Mushroom\Core\Route::class)));
$di->set(\Mushroom\Core\Route::class, \DI\create(\Mushroom\Core\Route::class));
$websocket = $di->make('websocket');
$websocket->start();