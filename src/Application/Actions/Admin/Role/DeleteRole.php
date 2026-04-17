<?php

namespace App\Application\Actions\Admin\Role;

use App\Application\Middleware\PermissionMiddleware;
use App\Service\Admin\AdminService;
use App\Service\Admin\RoleService;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class DeleteRole extends Action
{
  public function __construct(private readonly RoleService $role, private readonly AdminService $admin)
  {
  }

  /**
   * @inheritDoc
   */
  #[Route(
    path: '/admin/role/delete/{id:[0-9]+}',
    methods: ['DELETE'],
    description: '删除管理员角色',
    name: 'app.admin.role.delete',
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    $id = (int)$this->resolveArg('id', 0);
    if ($id <= 0) return $this->respondWithError('ID有误');
    $delNum = $this->role->delete($id);
    if ($delNum > 0) {
      $this->admin->clearRole($id);
      return $this->respondWithData(['delNum' => $delNum, 'message' => '管理员角色删除成功'], 204);
    } else {
      return $this->respondWithError('管理员角色删除失败');
    }
  }
}