#!/usr/bin/env php
<?php

require __DIR__ . '/../vendor/autoload.php';

use App\Entities\Common\AuditLogEntity;
use WanPHP\Core\ConfigLoader;
use WanPHP\Core\Factory\EntityMetadataFactory;
use WanPHP\Core\Worker\AuditStreamWorker;

define('ROOT_PATH', dirname(__DIR__));

ConfigLoader::load();
// Redis
$redis = new Redis();
$redis->connect(getenv('REDIS_HOST'), (int)getenv('REDIS_PORT'));
$redis->auth(getenv('REDIS_PASSWORD'));
$redis->select((int)getenv('QUEUE_REDIS_DB'));

$options = [
    'database_type' => getenv('DATABASE_TYPE'),
    'database_name' => getenv('DATABASE_NAME'),
    'server' => getenv('DATABASE_SERVER'),
    'username' => getenv('DATABASE_USER'),
    'password' => getenv('DATABASE_USER_PASSWORD'),

    'charset' => getenv('DATABASE_CHARSET'),
    'port' => getenv('DATABASE_PORT'),
    'prefix' => getenv('DATABASE_TABLE_PREFIX'),
    'logging' => getenv('DATABASE_LOGGING') === 'true',
    'option' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // 必须抛出异常
        PDO::ATTR_TIMEOUT => 5, // 数据库连接超时
        PDO::ATTR_PERSISTENT => true, // CLI模式下建议开启持久连接减少握手
        PDO::ATTR_CASE => PDO::CASE_NATURAL
    ],
    'command' => ['SET SQL_MODE=ANSI_QUOTES'],
    'error' => PDO::ERRMODE_EXCEPTION
];

try {
  $workerName = $argv[1] ?? null;

  if (!$workerName) {
    error_log("Usage: php bin/worker.php audit|order");
    exit(1);
  }

  switch ($workerName) {
    case 'audit':
      // 审计日志
      $meta = new EntityMetadataFactory()->from(AuditLogEntity::class);
      $consumer = gethostname() . '-' . getmypid();
      new AuditStreamWorker($redis, $options, $consumer, $meta->table)->run();
      break;
    case 'order':
      // 订单
      break;
  }

} catch (Exception $e) {
  error_log($e->getMessage());
}


