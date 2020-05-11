<?php

require "vendor/autoload.php";

$di = new Mushroom\Application(__DIR__);
$di->set('process', \DI\create(\Mushroom\Core\Process::class));
$di->set('request', \DI\create(\Mushroom\Core\Request::class));
$di->set('websocket', \DI\create(\Mushroom\Core\Http\Websocket::class));
$di->set(\Mushroom\Core\Redis::class, \DI\create(\Mushroom\Core\Redis::class));
$di->set(\Mushroom\Core\Route::class, \DI\create(\Mushroom\Core\Route::class));
$websocket = $di->make(\Mushroom\Core\Http\Websocket::class);
$websocket->start();