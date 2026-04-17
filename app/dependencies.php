<?php
declare(strict_types=1);

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use DI\ContainerBuilder;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger;
use Monolog\Processor\UidProcessor;
use Psr\Log\LoggerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Psr16Cache;
use WanPHP\Core\Database\EntityManager;
use WanPHP\Core\Factory\RedisCacheFactory;
use WanPHP\Core\Factory\RepositoryFactory;
use WanPHP\Core\Worker\RedisStream;


return function (ContainerBuilder $containerBuilder) {
  $definitions = [
    LoggerInterface::class => function () {
      $logPath = ROOT_PATH . (getenv('APP_LOG_PATH') ?: '/var/logs/app');
      if (!is_dir($logPath)) mkdir($logPath, 0755, true);

      $logger = new Logger('slim-app');
      $logger->pushProcessor(new UidProcessor());

      $lineFormatter = new LineFormatter("[%datetime%] %level_name%: %message% %context% %extra%\n");

      // debug.log — (DEBUG, INFO)
      $debugHandler = new RotatingFileHandler("$logPath/debug.log", 7, Logger::DEBUG, false);
      // app.log — (NOTICE, WARNING)
      $appHandler = new RotatingFileHandler("$logPath/app.log", 7, Logger::NOTICE, false);
      // error.log — (ERROR+)
      $errorHandler = new RotatingFileHandler("$logPath/error.log", 7, Logger::ERROR, false);

      $debugHandler->setFormatter($lineFormatter);
      $appHandler->setFormatter($lineFormatter);
      $errorHandler->setFormatter($lineFormatter);

      $logger->pushHandler($debugHandler);
      $logger->pushHandler($appHandler);
      $logger->pushHandler($errorHandler);

      return $logger;
    },
    EntityManager::class => function (RepositoryFactory $repositoryFactory) {
      return new WanPHP\Core\Database\EntityManager([
        'database_type' => getenv('DATABASE_TYPE'),
        'database_name' => getenv('DATABASE_NAME'),
        'server' => getenv('DATABASE_SERVER'),
        'username' => getenv('DATABASE_USER'),
        'password' => getenv('DATABASE_USER_PASSWORD'),

        'charset' => getenv('DATABASE_CHARSET'),
        'port' => getenv('DATABASE_PORT'),
        'prefix' => getenv('DATABASE_TABLE_PREFIX'),
        'logging' => getenv('DATABASE_LOGGING') === 'true',//启用日志
        'option' => [PDO::ATTR_CASE => PDO::CASE_NATURAL],
        'command' => ['SET SQL_MODE=ANSI_QUOTES'],
        'error' => PDO::ERRMODE_SILENT
      ], $repositoryFactory);
    },
    // 注册Redis
    RedisCacheFactory::class => function () {
      if (getenv('REDIS_HOST') && getenv('REDIS_PORT') && getenv('REDIS_PASSWORD')) {
        return new RedisCacheFactory(getenv('REDIS_HOST'), (int)getenv('REDIS_PORT'), getenv('REDIS_PASSWORD'));
      }
      return null;
    },
    // 注册缓存
    CacheInterface::class => function (RedisCacheFactory $redisCacheFactory) {
      return new Psr16Cache($redisCacheFactory->create((int)getenv('REDIS_DEFAULT_DB')));
    },
    // redis队列
    RedisStream::class => function () {
      $redis = new Redis();
      $redis->connect(getenv('REDIS_HOST'), (int)getenv('REDIS_PORT'));
      $redis->auth(getenv('REDIS_PASSWORD'));
      $redis->select((int)getenv('QUEUE_REDIS_DB'));
      return new RedisStream($redis);
    },
    // 生成二维码
    Writer::class => function () {
      $renderer = new ImageRenderer(new RendererStyle(480, 1), new SvgImageBackEnd());
      return new Writer($renderer);
    },
  ];
  $containerBuilder->addDefinitions($definitions);
};
