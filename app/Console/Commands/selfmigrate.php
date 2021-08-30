<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class selfmigrate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'self:migrate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Whotalk framework migrate';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //操作数据库迁移
        $this->info('Whotalk framework migrate successfully.');
        //系统无用文件清理
        Artisan::call('self:clear');
        return true;
    }
}
