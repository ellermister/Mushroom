<?php

namespace App\Console\Commands;
use Mushroom\Core\Console\Command;

class Test2 extends Command
{
    protected $signature = 'test2';

    protected $number = 10;

    protected $description = '入库';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle($id){
        echo 'test console info 1';
    }
}