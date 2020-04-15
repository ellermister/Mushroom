<?php


namespace Mushroom\Core\Console;
use Swoole\Process;

class Command
{
    protected $args = [];
    protected $options = [];
    protected $process = [];


    public function __construct()
    {

    }

    public function invoke(callable $fun)
    {
        $process = new Process($fun);
        $this->process[] = $process;
        return $process;
    }

    public function wait(callable $fun = null)
    {
        while ($ret = Process::wait(true)) {
            $fun && $fun($ret);
        }
    }

    public function setArgs($parameters)
    {
        $this->options = $parameters['options'];
        $this->args = $parameters['args'];
    }

    protected function info($message)
    {
        $this->output($message);
    }

    protected function error($message)
    {
        $this->output("\033[38;5;1m" . $message . "\033[0m");
    }

    protected function output($text)
    {
        echo $text . PHP_EOL;
    }

    protected function argument($name)
    {
        return $this->args[$name];
    }

    protected function arguments()
    {
        return $this->args;
    }

    protected function options()
    {
        return $this->options;
    }

    protected function option($name)
    {
        return $this->options[$name] ?? false;
    }

}