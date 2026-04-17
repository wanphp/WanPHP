<?php

namespace App\Application\Actions\Admin\User;

use App\Application\Middleware\PermissionMiddleware;
use App\Service\Admin\AdminService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\SimpleCache\CacheInterface;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class EditAdmin extends Action
{
  public function __construct(
    private readonly AdminService   $adminService,
    private readonly CacheInterface $cache
  )
  {
  }

  /**
   * @inheritDoc
   */
  #[Route(
    path: '/admin/edit/{id:[0-9]+}',
    methods: ['GET','POST'],
    description: '修改管理员',
    name: 'app.admin.edit',
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    if ($this->isPost()) {
      $data = $this->getFormData();
      $id = (int)$this->resolveArg('id', 0);
      $admin = $this->adminService->createAdmin($data);
      if ($this->adminService->checkAdmin($data['account'], $id)) return $this->respondWithError('帐号已经存在');
      $this->adminService->updateEntityToArray($admin->toArray(false), ['id' => $id]);
      $data['id'] = $id;
      unset($data['password']);
      $this->cache->delete('userInfo_' . $id);
      return $this->respondWithData($data);
    } else {
      $id = (int)$this->resolveArg('id', 0);
      if ($id < 1) return $this->respondWithError('ID错误');

      $data = [
        'title' => '修改管理员',
        'admin' => $this->adminService->load($id),
        'action' => $this->urlFor('app.admin.edit', ['id' => $id]),
        'modalName' => 'app.admin.modal',
        'roles' => $this->adminService->adminRole($this->getLoginUserRoleId()),
        'group' => $this->adminService->adminGroup()
      ];

      return $this->respondView('pages/admin/admin-modal.twig', $data);
    }
  }
}