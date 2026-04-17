<?php

namespace App\Application\Actions\Admin\Role;

use App\Application\Middleware\PermissionMiddleware;
use App\Service\Admin\RoleService;
use App\Service\Common\PersistenceService;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class AddRole extends Action
{
  public function __construct(
    private readonly RoleService        $role,
    private readonly PersistenceService $router)
  {
  }

  /**
   * @inheritDoc
   */
  #[Route(
    path: '/admin/role/add',
    methods: ['GET','POST'],
    description: '添加管理员角色',
    name: 'app.admin.role.add',
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    if ($this->isPost()) {
      $data = $this->getFormData();
      if ($this->role->checkRole($data['name'])) {
        return $this->respondWithData(['message' => '管理角色已经存在', 'errors' => ['name' => [$data['name'] . '角色重名']]], 422);
      }
      $data['id'] = $this->role->save($data);
      return $this->respondWithData($data, 201);
    } else {
      $routes = $this->router->select('id,name,description,path', ['isNav' => true]);
      $scopes = [];

      $data = [
        'title' => '添加配置角色权限',
        'role' => $role ?? [],
        'routes' => $routes,
        'scopes' => $scopes,
        'action' => $this->urlFor('app.admin.role.add'),
        'modalName' => 'app.admin.role.modal',
      ];

      return $this->respondView('pages/admin/role/role-modal.twig', $data);
    }
  }
}