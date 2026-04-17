<?php

namespace App\Application\Actions\Admin\User;

use App\Application\Middleware\PermissionMiddleware;
use App\Service\Admin\AdminService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\SimpleCache\CacheInterface;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class DeleteAdmin extends Action
{
  public function __construct(
    private readonly AdminService   $adminService,
    private readonly CacheInterface $cache)
  {
  }

  /**
   * @inheritDoc
   */
  #[Route(
    path: '/admin/delete/{id:[0-9]+}',
    methods: ['DELETE'],
    description: '删除管理员',
    name: 'app.admin.delete',
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    $id = (int)$this->resolveArg('id', 0);
    if ($id <= 0) return $this->respondWithError('ID有误');
    if ($this->getLoginId() == $id) return $this->respondWithError('不能删除自己');
    $delNum = $this->adminService->delete($id);
    if ($delNum > 0) {
      $this->cache->delete('userInfo_' . $id);
      return $this->respondWithData(['delNum' => $delNum, 'message' => '管理员删除成功'], 204);
    } else {
      return $this->respondWithError('管理员删除失败');
    }
  }
}