<?php

namespace App\Console\Commands;

use App\Models\Goods;
use App\Models\Order;
use Illuminate\Console\Command;
use Laravel\Octane\Facades\Octane;


class OctaneTestCommand extends Command
{
    /**
     * 命令签名：php artisan octane:test
     */
    protected $signature = 'octane-test';

    /**
     * 命令描述
     */
    protected $description = '测试 Octane 功能';


    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('octane:test 测试开始');

        [$goods, $order] = Octane::concurrently([
            fn() => Goods::query()->pluck('id')->toArray(),
            fn() => Order::query()->pluck('id')->toArray(),
        ]);

        dump($goods);
        dump($order);


        $this->info('octane:test 测试结束');
    }
}
