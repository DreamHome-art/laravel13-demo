<?php

/*
|--------------------------------------------------------------------------
| 创建 order_info 订单表迁移 (create_order_info_table)
|--------------------------------------------------------------------------
|
| 文件用途：创建 order_info 订单表，供 App\Models\Order 模型使用。
| 注意：Order 模型设置了 $timestamps = false，即不自动维护时间戳，
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
     * 执行迁移：创建 order_info 表
     */
    public function up(): void
    {
        Schema::create('order_info', function (Blueprint $table) {
            // 主键 ID（自增）
            $table->id();
            // 订单号（唯一索引）
            $table->string('order_no', 64)->unique()->comment('订单号');
            // 下单用户 ID
            $table->unsignedBigInteger('user_id')->default(0)->comment('下单用户ID');
            // 订单金额（最大 8 位整数 + 2 位小数）
            $table->decimal('amount', 10, 2)->default(0)->comment('订单金额');
            // 订单状态：0-待支付 1-已支付 2-已取消
            $table->unsignedTinyInteger('status')->default(0)->comment('订单状态：0待支付 1已支付 2已取消');
        });
    }

    /**
     * Reverse the migrations.
     * 回滚迁移：删除 order_info 表
     */
    public function down(): void
    {
        Schema::dropIfExists('order_info');
    }
};
