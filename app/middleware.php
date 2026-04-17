<?php
declare(strict_types=1);

use App\Application\Middleware\JsonBodyParserMiddleware;
use App\Application\Middleware\LoggerMiddleware;
use App\Application\Middleware\RefererMiddleware;
use App\Application\Middleware\SessionMiddleware;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Slim\Middleware\MethodOverrideMiddleware;
use Slim\App;
use Slim\Views\Twig;
use Slim\Views\TwigMiddleware;
use WanPHP\Core\Worker\RedisStream;

return function (App $app) {
  $app->add(new LoggerMiddleware($app->getContainer()->get(LoggerInterface::class), $app->getContainer()->get(RedisStream::class)));
  $app->add(new MethodOverrideMiddleware());
  $app->add(new JsonBodyParserMiddleware());
  $app->add(new SessionMiddleware($app->getContainer()->get(CacheInterface::class), getenv('APP_SESSION_NAME') ?: 'WANPHPSESSID'));
  $allowOrigin = getenv('APP_ALLOW_ORIGIN') ? array_map('trim', explode(',', getenv('APP_ALLOW_ORIGIN'))) : [];
  $app->add(new RefererMiddleware($allowOrigin));
  $app->addRoutingMiddleware();

  $paths = [ROOT_PATH . '/var/views'];

  // 插件页面命名空间
  foreach (glob(ROOT_PATH . '/wanphp/plugins/*/pages') as $path) {
    $parts = explode('/', $path);
    $namespace = $parts[count($parts) - 2];
    $paths[$namespace] = $path;
  }

  // BasePath 视图支持
  if ($app->getBasePath()) {
    $paths[str_replace('/', '', $app->getBasePath())] = ROOT_PATH . '/var' . $app->getBasePath();
  }

  $app->add(TwigMiddleware::create($app, Twig::create($paths)));
};
