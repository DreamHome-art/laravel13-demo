<?php

/*
|--------------------------------------------------------------------------
| 布隆过滤器控制器 (BloomFilterController)
|--------------------------------------------------------------------------
|
| 文件用途：提供布隆过滤器的 HTTP API 入口，对接 BloomFilterService 服务。
|           支持单个/批量添加、单个/批量查询、统计、清空等操作。
|
| 路由入口（在 routes/web.php 中注册）：
|   GET    /bloom/exists         查询单个元素是否存在
|   POST   /bloom/add            添加单个元素
|   POST   /bloom/addBatch       批量添加元素
|   POST   /bloom/existsBatch    批量查询元素
|   GET    /bloom/count          统计近似元素数量
|   DELETE /bloom/clear          清空过滤器
|
| 返回格式：
|   - 成功：JSON { "success": true, "data": ..., "message": "..." }
|   - 失败：JSON { "success": false, "error": "...", "message": "..." }
|
| 设计说明：
|   - 通过依赖注入使用 BloomFilterService（单例）
|   - 参数校验使用 Laravel 内置 validate
|   - 异常统一捕获并返回错误响应
|
*/

namespace App\Http\Controllers;

use App\Services\BloomFilterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BloomFilterController extends Controller
{
    /**
     * 布隆过滤器服务实例（通过依赖注入）
     */
    protected BloomFilterService $bloom;

    /**
     * 构造函数：注入布隆过滤器服务
     */
    public function __construct(BloomFilterService $bloom)
    {
        $this->bloom = $bloom;
    }

    /**
     * 添加单个元素到布隆过滤器
     *
     * 请求参数：
     *   - value: string 必填，待添加元素
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function add(Request $request): JsonResponse
    {
        // 参数校验：value 必填且非空
        $validated = $request->validate([
            'value' => 'required|string',
        ]);


        try {
            $this->bloom->add($validated['value']);

            return response()->json([
                'success' => true,
                'message' => '元素添加成功',
                'data' => [
                    'value' => $validated['value'],
                    'filter' => $this->bloom->getFilterName(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('添加元素失败', $e->getMessage());
        }
    }

    /**
     * 批量添加元素到布隆过滤器
     *
     * 请求参数：
     *   - values: array 必填，待添加元素数组（每个元素为字符串）
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function addBatch(Request $request): JsonResponse
    {
        // 参数校验：values 必填、为数组、至少一个元素
        $validated = $request->validate([
            'values' => 'required|array|min:1',
            'values.*' => 'string',
        ]);

        try {
            $count = $this->bloom->addBatch($validated['values']);

            return response()->json([
                'success' => true,
                'message' => '批量添加成功',
                'data' => [
                    'added_count' => $count,
                    'filter' => $this->bloom->getFilterName(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('批量添加失败', $e->getMessage());
        }
    }

    /**
     * 查询单个元素是否可能存在
     *
     * 请求参数：
     *   - value: string 必填，待查询元素
     *
     * 注意：返回 exists=true 表示"可能存在"（存在假阳性），
     *       exists=false 表示"一定不存在"（无假阴性）。
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function exists(Request $request): JsonResponse
    {
        // 参数校验：value 必填且非空
        $validated = $request->validate([
            'value' => 'required|string',
        ]);

        try {
            $exists = $this->bloom->exists($validated['value']);

            return response()->json([
                'success' => true,
                'message' => $exists ? '元素可能存在' : '元素一定不存在',
                'data' => [
                    'value' => $validated['value'],
                    'exists' => $exists,
                    'filter' => $this->bloom->getFilterName(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('查询元素失败', $e->getMessage());
        }
    }

    /**
     * 批量查询元素是否存在
     *
     * 请求参数：
     *   - values: array 必填，待查询元素数组
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function existsBatch(Request $request): JsonResponse
    {
        // 参数校验：values 必填、为数组、至少一个元素
        $validated = $request->validate([
            'values' => 'required|array|min:1',
            'values.*' => 'string',
        ]);

        try {
            $results = $this->bloom->existsBatch($validated['values']);

            return response()->json([
                'success' => true,
                'message' => '批量查询成功',
                'data' => [
                    'results' => $results,
                    'filter' => $this->bloom->getFilterName(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('批量查询失败', $e->getMessage());
        }
    }

    /**
     * 统计布隆过滤器中近似元素数量
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function count(Request $request): JsonResponse
    {
        try {
            $count = $this->bloom->count();

            return response()->json([
                'success' => true,
                'message' => '统计成功',
                'data' => [
                    'count' => $count,
                    'filter' => $this->bloom->getFilterName(),
                    'bit_size' => $this->bloom->getBitSize(),
                    'hash_count' => $this->bloom->getHashCount(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('统计失败', $e->getMessage());
        }
    }

    /**
     * 清空布隆过滤器
     *
     * @param  Request $request
     * @return JsonResponse
     */
    public function clear(Request $request): JsonResponse
    {
        try {
            $this->bloom->clear();

            return response()->json([
                'success' => true,
                'message' => '过滤器已清空',
                'data' => [
                    'filter' => $this->bloom->getFilterName(),
                ],
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse('清空过滤器失败', $e->getMessage());
        }
    }

    /*
    |----------------------------------------------------------------------
    | 内部辅助方法
    |----------------------------------------------------------------------
    */

    /**
     * 统一错误响应格式
     *
     * @param  string $message 用户友好的错误信息
     * @param  string $error   底层异常详情（用于调试）
     * @return JsonResponse
     */
    protected function errorResponse(string $message, string $error): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
            'error' => $error,
        ], 500);
    }
}
