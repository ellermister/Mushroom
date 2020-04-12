<?php

namespace App\Console\Commands;
use Mushroom\Core\Console\Command;

class Test extends Command
{
    protected $signature = 'test {user} {--queue}';

    protected $description = '测试';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle(){
        var_dump($this->option('queue'));
        $this->error('test console info 1');
    }
}