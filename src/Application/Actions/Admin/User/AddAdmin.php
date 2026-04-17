<?php

namespace App\Application\Actions\Admin\User;

use App\Application\Middleware\PermissionMiddleware;
use App\Service\Admin\AdminService;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class AddAdmin extends Action
{
  public function __construct(
    private readonly AdminService $adminService
  )
  {
  }

  /**
   * @inheritDoc
   */
  #[Route(path: '/admin/add',
    methods: ['GET','POST'],
    description: '添加管理员',
    name: 'app.admin.add',
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    if ($this->isPost()) {
      $data = $this->getFormData();
      if (empty($data['password'])) return $this->respondWithError('添加帐号必须设置密码');
      $admin = $this->adminService->createAdmin($data);
      if ($this->adminService->checkAdmin($data['account'])) return $this->respondWithError('帐号已经存在');
      $admin->setParentId($this->getLoginId())->setCreatedAt(time());
      $data['id'] = $this->adminService->insertEntityToArray($admin->toArray());
      unset($data['password']);
      return $this->respondWithData($data, 201);
    } else {
      $data = [
        'title' => '管理员',
        'admin' => $admin ?? [],
        'action' => $this->urlFor('app.admin.add'),
        'modalName' => 'app.admin.modal',
        'roles' => $this->adminService->adminRole($this->getLoginUserRoleId()),
        'group' => $this->adminService->adminGroup(),
      ];

      return $this->respondView('pages/admin/admin-modal.twig', $data);
    }
  }
}