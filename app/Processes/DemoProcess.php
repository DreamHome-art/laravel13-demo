<?php

namespace App\Processes;

use Hhxsv5\LaravelS\Swoole\Process\CustomProcessInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Coroutine;
use Swoole\Process;
use Swoole\Server;

class DemoProcess implements CustomProcessInterface
{
    public static function callback(Server $server, Process $process)
    {
        while (true) {
            echo "[自定义进程]" . date('Y-m-d H:i:s') . "\n";

            Coroutine::sleep(2);

            Log::info('自定义进程定时任务', [time()]);

            sleep(3);
        }
    }

    public static function onReload(Server $server, Process $process)
    {
        // 重载回调
        echo "自定义进程收到 reload\n";
    }
}
