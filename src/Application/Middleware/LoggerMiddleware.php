<?php
declare(strict_types=1);

namespace App\Application\Middleware;


use App\Entities\Common\AuditLogEntity;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Random\RandomException;
use Slim\Routing\RouteContext;
use Throwable;
use WanPHP\Core\Worker\AuditLogContext;
use WanPHP\Core\Worker\RedisStream;

final readonly class LoggerMiddleware implements MiddlewareInterface
{
  public function __construct(private LoggerInterface $logger, private RedisStream $redisStream)
  {
  }

  /**
   * @throws Throwable
   */
  public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
  {
    $start = microtime(true);
    AuditLogContext::start();

    try {
      $response = $handler->handle($request);
      $exception = null;
    } catch (Throwable $e) {
      $exception = $e;
      throw $e;
    } finally {
      $this->log($request, $response ?? null, $exception, $start);

      AuditLogContext::clear();
    }

    return $response;
  }

  private function log(ServerRequestInterface $request, ?ResponseInterface $response, ?Throwable $exception, float $startTime): void
  {
    $timeMs = round((microtime(true) - $startTime) * 1000, 2);

    try {
      $request_id = $request->getHeaderLine('X-Request-Id') ?: bin2hex(random_bytes(16));
    } catch (RandomException $e) {
      $request_id = null;
    }

    $ua = $request->getHeaderLine('User-Agent');

    $routeContext = RouteContext::fromRequest($request);

    $routeName = $routeContext->getRoute()->getName();

    $method = $request->getMethod();
    $uri = $request->getUri()->getPath();

    $status = $response?->getStatusCode() ?? 500;

    $serverParams = $request->getServerParams();
    if (!empty($serverParams['HTTP_X_FORWARDED_FOR'])) {
      // 多个代理服务器的情况下，获取最后一个IP地址
      $ipList = explode(',', $serverParams['HTTP_X_FORWARDED_FOR']);
      $ipAddress = trim(end($ipList));
    } else {
      $ipAddress = $serverParams['REMOTE_ADDR'] ?? null;
    }

    $ctx = AuditLogContext::current();
    if ($ctx->debug) {
      $data = [
        'request_id' => $request_id,
        'method' => $method,
        'action' => $routeName ?: "",
        'status' => $status,
        'duration' => $timeMs . 'ms',
        'actor' => $ctx->actor,
        'ip' => $ipAddress,
        'ua' => $ua,
        'entries' => $ctx->entries,
      ];

      if ($exception) {
        $data['exception'] = [
          'type' => get_class($exception),
          'message' => $exception->getMessage(),
        ];
      }

      // 程序调试
      $this->logger->debug($uri, $data);
    } else if ($ctx && $ctx->audit) {// 日志审计
      $cacheFile = ROOT_PATH . '/var/cache/routes.cache.php';
      // 当前路有描述
      $actions = [];
      if (file_exists($cacheFile)) {
        $cachedRoutes = require $cacheFile;
        if (is_array($cachedRoutes)) $actions = array_column($cachedRoutes, 'description', 'name');
      }

      foreach ($ctx->entries as $audit) {
        //$this->logger->debug($request_id, $audit);
        $context = (array)$audit['changes'] ?? [];
        $log = new AuditLogEntity()->setEventTime(time())
          ->setRequestId($request_id)
          ->setActorId($ctx->actor['id'] ?? null)
          ->setActorType($ctx->actor['type'] ?? 'system')
          ->setClientId($ctx->actor['clientId'] ?? null)
          ->setAction($audit['action'])
          ->setActionDesc($actions[$routeName])
          ->setResource($audit['resource'])
          ->setResourceId($audit['id'])
          ->setContext($context)
          ->setMethod($method)
          ->setRoute($routeName)
          ->setIp($ipAddress)
          ->setUserAgent($ua);

        // 写入审计日志
        try {
          $this->redisStream->push('audit:stream', $log->toArray());
        } catch (\Exception $e) {
          $this->logger->error($e->getMessage());
        }
      }
    }
  }
}
