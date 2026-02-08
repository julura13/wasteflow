<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Database\Seeders\MonthlyReportDataSeeder;

class RunMonthlyReportSeeder extends Command
{
    protected $signature = 'seed:monthly-report';
    protected $description = 'Seed sample data for monthly waste management report (ABC Company)';

    public function handle()
    {
        $this->info('Seeding monthly report data for ABC Company...');
        
        $seeder = new MonthlyReportDataSeeder();
        $seeder->setCommand($this);
        $seeder->run();
        
        $this->info('Done! You can now generate the monthly report for ABC Company.');
        return 0;
    }
}
