<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Cache;

class CacheTestController
{


    public function cacheMsg()
    {
        // cache 设置缓存信息
        $re = Cache::set('user2', ['name' => 'tom'], 3600);
        dd($re);
    }


}
