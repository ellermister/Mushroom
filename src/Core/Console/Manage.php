<?php


namespace Mushroom\Core\Console;


use Mushroom\Application;

class Manage
{
    protected $application;
    protected $console;

    public function __construct(Application $application, Kernel $console)
    {
        $this->application = $application;
        $this->console = $console;
    }

    public function start()
    {
        $options = $GLOBALS['argv'];
        array_shift($options);

        if (count($options) === 0) {
            $this->console->help();
        } else {
            $target = $options[0];
            $this->dispatch($options[0], $options);
        }
    }

    protected function dispatch($target, $input)
    {
        $parameters = [];
        foreach ($this->console->getConsoles() as $signature => $command) {
            if (trim($signature) == trim($target)) {
                $object = $this->application->make($command['class']);
                $parameters = $this->parseArguments($command['signature'], $input);
                $object->setArgs($parameters);
                $this->application->call([$object, 'handle'], $parameters);
            }
        }
    }

    protected function parseArguments($signature, $input)
    {
        $option = [];
        $args = [];
        array_shift($input);
        if (preg_match_all('/\{([^}]+)\}/is', $signature, $matches)) {
            foreach ($matches[1] as $value) {
                if (strpos($value, '--') === false) {
                    // arg
                    $args[$value] = null;
                } else {
                    // option
                    $option[$value] = null;
                }
            }
        }
        $offset = 0;
        array_walk($args,function (&$value,$name) use($offset,$input){
            if (isset($input[$offset])) {
                $value = $input[$offset];
            }
            $offset++;
        });

        $options = [];
        foreach ($option as $name => $value) {
            $value = $this->getOptionValue($name, $input);
            $options[trim($name, '-')] = $value;
        }
        return [
            'options' => $options,
            'args'    => $args,
        ];
    }

    protected function getOptionValue($option, $input)
    {
        foreach ($input as $value) {
            $name = trim($option, '-');
            if (preg_match('/^--' . $name . '(=(\S+))?/is', $value, $match)) {
                if (isset($match[2])) {
                    return $match[2];
                }
                return true;
            }
        }
        return false;
    }
}