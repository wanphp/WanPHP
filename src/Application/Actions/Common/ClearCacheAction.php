<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/21
 * Time: 16:34
 */

namespace App\Application\Actions\Common;


use App\Application\Middleware\PermissionMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\SimpleCache\CacheInterface;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class ClearCacheAction extends Action
{

  public function __construct(private readonly CacheInterface $cache)
  {
  }

  #[Route(
    path: '/clearCache',
    methods: ['GET'],
    description: '清除缓存记录',
    name: 'app.clearCache',
    middleware: [PermissionMiddleware::class])
  ]
  protected function action(): Response
  {
    return $this->respondWithData(['reload' => true, 'message' => '清除缓存记录:' . $this->cache->clear() . '!']);
  }
}
