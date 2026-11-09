<?php

/*
|--------------------------------------------------------------------------
| 创建 goods 商品表迁移 (create_goods_table)
|--------------------------------------------------------------------------
|
| 文件用途：创建 goods 商品表，供 App\Models\Goods 模型使用。
| 注意：Goods 模型设置了 $timestamps = false，即不自动维护时间戳，
| 因此本表不包含 created_at / updated_at 字段。
|
*/

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 执行迁移：创建 goods 表
     */
    public function up(): void
    {
        Schema::create('goods', function (Blueprint $table) {
            // 主键 ID（自增）
            $table->id();
            // 商品名称
            $table->string('name')->comment('商品名称');
            // 商品价格（最大 8 位整数 + 2 位小数）
            $table->decimal('price', 10, 2)->default(0)->comment('商品价格');
            // 库存数量
            $table->unsignedInteger('stock')->default(0)->comment('库存数量');
        });
    }

    /**
     * Reverse the migrations.
     * 回滚迁移：删除 goods 表
     */
    public function down(): void
    {
        Schema::dropIfExists('goods');
    }
};
