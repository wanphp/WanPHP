<?php
declare(strict_types=1);

use App\Application\Handlers\HttpErrorHandler;
use App\Application\Handlers\ShutdownHandler;
use App\Application\ResponseEmitter\ResponseEmitter;
use DI\ContainerBuilder;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Slim\Factory\ServerRequestCreatorFactory;
use WanPHP\Core\ConfigLoader;

define('ROOT_PATH', dirname(__DIR__));
putenv('ROOT_PATH=' . ROOT_PATH);
require ROOT_PATH . '/vendor/autoload.php';
// 实例化 PHP-DI ContainerBuilder
$containerBuilder = new ContainerBuilder();

// 未注入配置，加载并写入配置
ConfigLoader::load();
// 生产环境
if (getenv('APP_ENV') === 'prod') {
  $containerBuilder->enableCompilation(ROOT_PATH . '/var/cache');
} else {
  ini_set("display_errors", "On");   //开启错误提示
  error_reporting(E_ALL);     //显示错有错误
}

// 设置依赖
$dependencies = require ROOT_PATH . '/app/dependencies.php';
$dependencies($containerBuilder);

// 设置存储库
$repositories = require ROOT_PATH . '/app/repositories.php';
$repositories($containerBuilder);

// Build PHP-DI Container instance
$container = $containerBuilder->build();

// Instantiate the app
AppFactory::setContainer($container);
$app = AppFactory::create();
$basePath = getenv('APP_BASE_PATH');
if ($basePath) $app->setBasePath($basePath);
$callableResolver = $app->getCallableResolver();

// Register middleware
$middleware = require ROOT_PATH . '/app/middleware.php';
$middleware($app);

// Register routes
$routes = require ROOT_PATH . '/app/routes.php';
$routes($app);

// 在生产中为false
$displayErrorDetails = getenv('APP_ENV') !== 'prod';

// Create Request object from globals
$serverRequestCreator = ServerRequestCreatorFactory::create();
$request = $serverRequestCreator->createServerRequestFromGlobals();

// 创建错误处理程序
$responseFactory = $app->getResponseFactory();
$errorHandler = new HttpErrorHandler($callableResolver, $responseFactory, $container->get(LoggerInterface::class));

// Create Shutdown Handler
$shutdownHandler = new ShutdownHandler($request, $errorHandler, $displayErrorDetails);
register_shutdown_function($shutdownHandler);

// 添加错误中间件
$errorMiddleware = $app->addErrorMiddleware($displayErrorDetails, false, false);
$errorMiddleware->setDefaultErrorHandler($errorHandler);

// 运行应用并发出响应
$response = $app->handle($request);
$responseEmitter = new ResponseEmitter();
$responseEmitter->emit($response);
