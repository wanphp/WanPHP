<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/9/10
 * Time: 16:08
 */

namespace App\Application\Actions\Admin\Role;


use App\Application\Middleware\PermissionMiddleware;
use App\Service\Admin\RoleService;
use App\Service\Common\PersistenceService;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class RoleAction extends Action
{

  public function __construct(
    private readonly RoleService        $role,
    private readonly PersistenceService $router)
  {
  }

  #[Route(path: '/admin/role[/{id:[0-9]+}]', methods: ['GET'], description: '管理员角色管理', name: 'app.admin.role', isNav: true, middleware: [PermissionMiddleware::class])]
  protected function action(): Response
  {
    if ($this->isAjax()) {
      $actions = $this->router->select('id,name,description,path', ['isNav' => true]);
      $actions = array_column($actions, 'description', 'id');
      foreach ($this->role->select('id,name,scopes[JSON]') as $role) {
        $restricted = [];
        foreach ($role['scopes'] as $id) {
          $restricted[] = $actions[$id];
        }
        $role['restricted'] = implode('、', $restricted);
        $role['actions'] = [
          'edit' => [
            'name' => '修改',
            'path' => $this->urlFor('app.admin.role.edit', ['id' => $role['id']]),
            'modal' => ['name' => 'app.admin.role.modal', 'size' => 'lg'],
          ],
          'delete' => [
            'name' => '删除',
            'path' => $this->urlFor('app.admin.role.delete', ['id' => $role['id']]),
            'message' => '是否确认要删除此角色'
          ]
        ];
        $roles[] = $role;
      }
      return $this->respondWithData([
        'data' => $roles ?? []
      ]);
    } else {

      $data = [
        'title' => '角色管理'
      ];

      return $this->respondView('pages/admin/role/index.twig', $data);
    }
  }
}
