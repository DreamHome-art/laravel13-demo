<?php

namespace App\Services;

use Hhxsv5\LaravelS\Swoole\WebSocketHandlerInterface;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;

class WebSocketService implements WebSocketHandlerInterface
{
    // 连接打开
    public function onOpen(Server $server, Request $request)
    {
        $fd = $request->fd;
        echo "新连接 fd:{$fd}\n";
        $server->push($fd, "欢迎连接 LaravelS WebSocket");
    }

    // 收到消息
    public function onMessage(Server $server, Frame $frame)
    {
        $fd = $frame->fd;
        $data = $frame->data;
        echo "收到消息 fd:{$fd}\n data:{$data}\n";
        // 回射
        $server->push($fd, "服务端收到：" . $data);
    }

    // 连接关闭
    public function onClose(Server $server, $fd, $reactorId)
    {
        echo "连接关闭 fd:{$fd}\n";
    }


}
