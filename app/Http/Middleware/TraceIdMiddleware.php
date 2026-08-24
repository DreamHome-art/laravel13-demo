<?php

namespace App\Http\Middleware;

use App\Support\TraceIdContext;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * TraceId 全局中间件
 *
 * 为每个 HTTP 请求生成（或继承上游传递的）全局唯一标识 trace_id，
 * 并通过 TraceIdContext::set() 存入静态上下文。
 * Monolog 的 TraceIdProcessor 会从该上下文读取 trace_id，
 * 自动注入到所有日志通道（含 SQL 查询日志）的每条记录中，
 * 便于后续按 trace_id 进行全链路日志检索与排查。
 *
 * 优先级说明：
 * 1. 优先读取上游服务通过请求头 X-Trace-Id 传递的 trace_id（用于微服务链路串联）
 * 2. 若未传递则自动生成一个带日期前缀的唯一 ID（便于按日期定位日志文件）
 *
 * 同时通过响应头 X-Trace-Id 返回，便于客户端反馈与排查问题。
 */
class TraceIdMiddleware
{
    /**
     * 请求头名称：用于接收上游传递的 trace_id
     */
    private const HEADER_TRACE_ID = 'X-Trace-Id';

    /**
     * 处理传入请求，注入 trace_id 到日志上下文
     *
     * @param  Request  $request  当前 HTTP 请求实例
     * @param  Closure  $next     下一层中间件 / 控制器
     * @return Response           处理后的响应
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. 解析 trace_id：优先使用上游传递的，否则自动生成
        $traceId = $this->resolveTraceId($request);

        // 2. 将 trace_id 写入全局上下文，供 Monolog Processor 读取
        //    所有日志通道（含 SQL 日志）的记录都会自动携带该字段
        TraceIdContext::set($traceId);

        // 3. 将 trace_id 写入请求 attributes，便于控制器/其他中间件读取
        $request->attributes->set('trace_id', $traceId);

        // 4. 继续处理请求
        $response = $next($request);

        // 5. 在响应头中返回 trace_id，方便客户端反馈与排查
        $response->headers->set(self::HEADER_TRACE_ID, $traceId);

        return $response;
    }

    /**
     * 在请求结束后清理 trace_id 上下文
     * 避免常驻进程模式（Octane/RoadRunner）下上下文污染到下一个请求
     *
     * @param  Request   $request
     * @param  Response  $response
     * @return void
     */
    public function terminate(Request $request, Response $response): void
    {
        TraceIdContext::reset();
    }

    /**
     * 解析当前请求的 trace_id
     *
     * 优先级：上游请求头 > 自动生成
     *
     * @param  Request  $request  当前 HTTP 请求实例
     * @return string             解析得到的 trace_id
     */
    protected function resolveTraceId(Request $request): string
    {
        // 优先读取上游通过请求头传递的 trace_id（微服务串联场景）
        $upstreamTraceId = $request->headers->get(self::HEADER_TRACE_ID);

        if (!empty($upstreamTraceId) && $this->isValidTraceId($upstreamTraceId)) {
            return $upstreamTraceId;
        }

        // 否则生成一个带日期前缀的唯一 ID，便于按日期定位日志文件
        // 格式：Ymd-His-随机串(8位)，示例：20260823-140935-a1b2c3d4
        return sprintf(
            '%s-%s-%s',
            now()->format('Ymd'),
            now()->format('His'),
            Str::random(8)
        );
    }

    /**
     * 校验上游传递的 trace_id 是否合法
     * 仅允许字母、数字、连字符，长度限制在 8~64，避免恶意输入污染日志
     *
     * @param  string  $traceId  待校验的 trace_id
     * @return bool              是否合法
     */
    protected function isValidTraceId(string $traceId): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9\-]{8,64}$/', $traceId);
    }
}
