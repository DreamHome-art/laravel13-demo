<?php

namespace App\Http\Controllers;


//use Illuminate\Support\Facades\Request;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use PHPUnit\Event\Runtime\PHP;

//use Illuminate\Http\Client\Request;
//use http\Client\Request;
//use http\Env\Request;
//use GuzzleHttp\Psr7\Request;

class RedisTestController
{

    /**
     * Redis 设置数据
     */
    public function setMsg(Request $request)
    {
        $info = Redis::smembers('tag');
        dd($info);

        // set 集合
        $re = Redis::sadd('tag','php','go');
        dd($re);


        // 队列弹出数据
        $info = Redis::rpop('queue');
        dd($info);

        // 设置队列数据
//        $re = Redis::lpush('queue','msg1');
        $arr = [
            ['name' => '张三', 'sex' => '男'],
            ['name' => '李思', 'sex' => '女'],
            ['name' => '王武', 'sex' => '男'],
        ];

        $re = Redis::rPush('queue', $arr);
        dd($re);

        $json_arr = json_encode($arr, JSON_UNESCAPED_UNICODE);
//        $re = Redis::lpush('queue','msg1');

        $re = Redis::lPush('queue', $json_arr);

        dd($re);

        // 获取hash表数据
        $info = Redis::hgetall('user:1');
        dd($info);

//        $re = Redis::hset('user:1','name','张三');
        $re = Redis::hset('user:2','name','李四');
        dd($re);


        // 批量获取值
        $info = Redis::mget(['name', 'sex']);
        dd($info);


        $name = Redis::get('name');
        dd($name);

        $name_type = Redis::type('sex');
        dd($name_type);


        // 设置key并同步设置过期时间
        $re = Redis::setex('k',60,'v');
        dd($re);


        $key = $request->input('key');
        $name = $request->input('name');

        $re = Redis::set($key,$name);
        if ($re) {
            echo "添加Redis数据成功" . PHP_EOL;

            $res = Redis::expire($key,60);
            if ($res) {
                echo "添加Redis key的过期时间" . PHP_EOL;
            }
        }

        $info = Redis::get($key);
        dd($info);
    }

    /**
     * Redis 管道
     */
    public function pipelineMsg()
    {
        $res = Redis::pipeline(
            function ($pipe) {
                $pipe->set('name', 'lisi');
                $pipe->set('sex', 'man');
            }
        );

        dd($res);
    }

    /**
     * Redis 事务
     */
    public function multiMsg()
    {
        $re = Redis::multi()->set('a', 1)->incr('a')->exec();

        dd($re);

    }



}
