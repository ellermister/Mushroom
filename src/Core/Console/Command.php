<?php


namespace Mushroom\Core\Console;


class Command
{
    protected $args = [];
    protected $options = [];


    public function __construct()
    {

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