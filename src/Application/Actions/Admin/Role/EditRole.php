<?php

namespace App\Application\Actions\Admin\Role;

use App\Application\Middleware\PermissionMiddleware;
use App\Service\Admin\RoleService;
use App\Service\Common\PersistenceService;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class EditRole extends Action
{
  public function __construct(private readonly RoleService $role, private readonly PersistenceService $router)
  {
  }

  /**
   * @inheritDoc
   */
  #[Route(
    path: '/admin/role/edit/{id:[0-9]+}',
    methods: ['GET', 'POST'],
    description: '添加管理员角色',
    name: 'app.admin.role.edit',
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    $id = (int)$this->resolveArg('id', 0);
    if ($id < 1) return $this->respondWithError('ID错误');
    if ($this->isPost()) {
      $data = $this->getFormData();
      if ($this->role->checkRole($data['name'], $id)) {
        return $this->respondWithData(['message' => '管理角色已经存在', 'errors' => ['name' => [$data['name'] . '角色重名']]], 422);
      }
      $this->role->update($id, $data);
      $data['id'] = $id;
      return $this->respondWithData($data);
    } else {
      $role = $this->role->getRole($id);
      if (!empty($role['scopes'])) {
        $routes = $this->router->select('id,name,description,path', ['isNav' => true, 'id[!]' => $role['scopes']]);
        $scopes = $this->router->select('id,name,description,path', ['isNav' => true, 'id' => $role['scopes']]);
      } else {
        $routes = $this->router->select('id,name,description,path', ['isNav' => true]);
        $scopes = [];
      }

      $data = [
        'title' => '添加配置角色权限',
        'role' => $role,
        'routes' => $routes,
        'scopes' => $scopes,
        'action' => $this->urlFor('app.admin.role.edit', ['id' => $id]),
        'modalName' => 'app.admin.role.modal',
      ];

      return $this->respondView('pages/admin/role/role-modal.twig', $data);
    }
  }
}