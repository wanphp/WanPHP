<?php

namespace App\Application\Actions\Admin\Group;

use App\Application\Middleware\PermissionMiddleware;
use App\Service\Admin\AdminService;
use App\Service\Admin\GroupService;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class DeleteGroup extends Action
{
  public function __construct(
    private readonly GroupService $groupService,
    private readonly AdminService $adminService)
  {
  }

  /**
   * @inheritDoc
   */
  #[Route(
    path: '/admin/group/delete/{id:[0-9]+}',
    methods: ['DELETE'],
    description: '删除管理员分组',
    name: 'app.admin.group.delete',
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    $id = (int)$this->resolveArg('id', 0);
    if ($id > 0) {
      $delNum = $this->groupService->delete($id);
      if ($delNum > 0) {
        $this->adminService->clearGroup($id);
        return $this->respondWithData(['delNum' => $delNum, 'message' => '分组删除成功'], 204);
      } else {
        return $this->respondWithError('分组删除失败');
      }
    } else {
      return $this->respondWithError('ID有误');
    }

  }
}