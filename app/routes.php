<?php
declare(strict_types=1);

use App\Application\Middleware\PermissionMiddleware;
use App\Service\Common\PersistenceService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Factory\ClassScannerFactory;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;

return function (App $app) {
  $app->options('/{routes:.*}', function (Request $request, Response $response) {
    // CORS Pre-Flight OPTIONS Request Handler
    return $response;
  });

  $cacheFile = ROOT_PATH . '/var/cache/routes.cache.php';
  $cachedRoutes = [];

  if (getenv('APP_ENV') === 'prod' && file_exists($cacheFile)) {
    // 缓存命中：直接加载缓存文件
    $cachedRoutes = require $cacheFile;
    // 确保加载的是数组，防止文件损坏导致意外
    if (!is_array($cachedRoutes)) {
      $cachedRoutes = []; // 重置并继续到生成逻辑
    }
  }

  if (empty($cachedRoutes)) {
    $paths = [
      ROOT_PATH . '/src/Application/Actions',
      ROOT_PATH . '/src/Application/Api'
    ];
    // Single Page Application
    if (file_exists(ROOT_PATH . '/src/Application/Spa')) $paths[] = ROOT_PATH . '/src/Application/Spa';
    // 服务端设置了私钥，添加路由
    if (getenv('OAUTH2_PRIVATE_KEY')) $paths[] = ROOT_PATH . '/vendor/wanphp/core/src/AuthAction';

    $actionPath = glob(ROOT_PATH . '/wanphp/plugins/*/src/Application', GLOB_ONLYDIR);
    if ($actionPath) $paths = array_merge($paths, $actionPath);

    // 权限
    $permissions = [];
    // 扫描 Action 目录
    $classes = ClassScannerFactory::scanDirectories($paths);
    foreach ($classes as $className) {
      $reflection = new ReflectionClass($className);
      if (!$reflection->isInstantiable()) continue;

      $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
      if ($reflection->hasMethod('action')) $methods[] = $reflection->getMethod('action');
      foreach ($methods as $method) {
        foreach ($method->getAttributes(Route::class) as $attr) {
          /** @var Route $route */
          $route = $attr->newInstance();

          // 确保 Route 对象有 path 属性，否则跳过
          if (!$route->path) continue;

          // 构建要缓存的数据结构
          $callable = $method->getName() === 'action' ? $className : $className . ':' . $method->getName();
          $routeEntry = [
            'path' => $route->path,
            'methods' => $route->methods,
            'callable' => $callable,
            'name' => $route->name ?? null,
            'description' => $route->description ?? null,
            'isNav' => $route->isNav ?? false,
            'middleware' => [],
          ];

          // 缓存中间件类名
          foreach ($route->middleware as $mwClass) {
            if (is_string($mwClass)) {
              if (class_exists($mwClass) || interface_exists($mwClass)) {
                $routeEntry['middleware'][] = $mwClass;
                if ($mwClass == PermissionMiddleware::class || $mwClass == AdminPermissionMiddlewareInterface::class) {
                  $permissions[] = $routeEntry;
                }
              }
            } elseif (is_array($mwClass) && isset($mwClass[0])) {
              $class = $mwClass[0];
              $params = $mwClass[1] ?? [];

              if (class_exists($class)) {
                $routeEntry['middleware'][] = [$class, $params];
              }
            }
          }

          $cachedRoutes[] = $routeEntry;
        }
      }
    }
    // 写入缓存文件
    if (!file_exists($cacheFile) && !empty($cachedRoutes)) {
      // 确保缓存目录存在
      $cacheDir = dirname($cacheFile);
      if (!is_dir($cacheDir)) {
        mkdir($cacheDir, 0755, true);
      }

      // 使用 var_export 确保生成合法的 PHP 数组文件
      $content = "<?php\n\nreturn " . var_export($cachedRoutes, true) . ";\n";
      file_put_contents($cacheFile, $content);
      // 权限写入到数据库
      $permissionService = $app->getContainer()->get(PersistenceService::class);
      $permissionService->syncRoutes($permissions);
    }
  }

  // 遍历缓存或新生成的路由，注册到 Slim App
  foreach ($cachedRoutes as $route) {
    // 注册 Slim 路由
    $r = $app->map($route['methods'], $route['path'], $route['callable']);

    // 设置路由名
    if ($route['name']) {
      $r->setName($route['name']);
    }

    // 添加中间件,在这里实例化中间件
    if (!empty($route['middleware'])) {
      foreach ($route['middleware'] as $mw) {
        if (is_string($mw)) {
          $middlewareInstance = $app->getContainer()->get($mw);
          $r->add($middlewareInstance);
          continue;
        }

        if (is_array($mw)) {
          [$class, $params] = $mw;

          if (!is_array($params)) {
            $params = [$params];
          }
          $r->add(new $class(...$params));
        }
      }
    }
  }
};
