<?php
namespace App\Console;
use Mushroom\Core\Console\Schedule;
use Mushroom\Core\Console\Kernel as ConsoleKernel;
class Kernel extends ConsoleKernel
{
    protected $commands = [
        //
    ];

    protected function schedule()
    {
        // $schedule->command('inspire')
        //          ->hourly();
    }

    protected function commands()
    {
        $this->load(__DIR__.'/Commands');
    }
}