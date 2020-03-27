<?php
require "vendor/autoload.php";

$di = new Mushroom\Application(__DIR__);
$di->set('process', \DI\create(\Mushroom\Core\Process::class));
$di->set('request', \DI\create(\Mushroom\Core\Request::class));
$process = $di->make('process');
$process->start();