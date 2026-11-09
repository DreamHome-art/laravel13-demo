<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Goods extends Model
{
    /**
     * 与模型相关的数据表
     * @var string
     */
    public $table = 'goods';

    /**
     * 指示模型是否主动维护时间戳
     * @var bool
     */
    public $timestamps = false;
}
