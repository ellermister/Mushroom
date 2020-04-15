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
        foreach(range(1,3) as $index){
            $this->invoke(function()use($index){
                $this->info('线程:'.$index.',即将开始\n');
                sleep($index);
                $this->info('线程:'.$index.',已经结束\n');
            })->start();
        }
        $this->wait(function ($ret){
//            echo "PID={$ret['pid']}\n";
        });
        echo '所有任务已经执行完毕\n';
    }
}