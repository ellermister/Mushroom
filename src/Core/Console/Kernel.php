<?php


namespace Mushroom\Core\Console;


abstract Class Kernel
{
    protected $commands = [];

    abstract protected function commands();

    abstract protected function schedule();

    public function __construct()
    {
        $this->commands();
    }

    protected function load($dir)
    {
        $handler = opendir($dir);
        while (($filename = readdir($handler)) !== false) {
            if ($filename != "." && $filename != "..") {
                $pos = strpos($dir, app()->getBasePath());
                $namespace = substr($dir, $pos + strlen(app()->getBasePath()));
                $namespace = ucfirst(trim($namespace, '/'));
                $pathInfo = pathinfo($filename);
                if ($pathInfo["extension"] == 'php') {
                    $short = $pathInfo["filename"];
                    $commandInfo = [
                        'class' => str_replace('/', '\\', $namespace . DIRECTORY_SEPARATOR . $short),
                        'file'  => $filename,
                        'path'  => $dir . DIRECTORY_SEPARATOR . $filename
                    ];

                    $class = new \ReflectionClass($commandInfo['class']);
                    $default = $class->getDefaultProperties();
                    $_arr = explode(' ',$default['signature']);
                    $signature = array_shift($_arr);
                    if(isset($commands[$signature])){
                        throw new \Exception("命名不能重复定义:".$default['signature'].' in '.$class->getFileName());
                    }
                    $commandInfo['description'] = $default['description'];
                    $commandInfo['signature'] = $default['signature'];
                    $this->commands[$signature] = $commandInfo;
                }
            }
        }
        closedir($handler);
    }

    public function getConsoles()
    {
        return $this->commands;
    }

    public function help()
    {
        echo 'Please input command:' . PHP_EOL;
        foreach($this->getConsoles() as $signature => $command){
            echo "    {$signature} - {$command['description']}" . PHP_EOL;
        }
    }
}