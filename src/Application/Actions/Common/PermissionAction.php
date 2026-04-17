<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/9/7
 * Time: 16:58
 */

namespace App\Application\Actions\Common;


use App\Application\Middleware\PermissionMiddleware;
use App\Service\Common\NavigateService;
use App\Service\Common\PersistenceService;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class PermissionAction extends Action
{

  public function __construct(private readonly PersistenceService $router, private readonly NavigateService $navigate)
  {
  }

  #[Route(path: '/admin/permission[/{id:[0-9]+}]', methods: ['GET', 'POST'], description: '权限管理', name: 'app.permission', isNav: true, middleware: [PermissionMiddleware::class])]
  protected function action(): Response
  {
    switch ($this->request->getMethod()) {
      case  'POST':
        $data = $this->getFormData();
        $id = (int)$this->resolveArg('id', 0);
        if ($id > 0) {
          if (!empty($data['id'])) {
            $num = $this->router->updateSortOrder($id, $data['id'], $data['newIndex'], $data['oldIndex']);
          } else {
            $num = $this->router->updateNavId($id, $data['navId']);
          }
          return $this->respondWithData(['upNum' => $num]);
        } else {
          return $this->respondWithError('未知操作');
        }
      case 'GET':
        $navigate = $this->navigate->select('*', ['ORDER' => ['sortOrder' => 'ASC']]);
        $menus = [];
        foreach ($navigate as $item) {
          $menus[$item['id']] = $item;
        }
        $actions = $this->router->select('id,navId,name,description,path', ['isNav' => true, 'ORDER' => ['navId' => 'ASC', 'sortOrder' => 'ASC']]);
        foreach ($actions as $action) {
          if ($action['navId'] > 0) $menus[$action['navId']]['sublist'][] = ['id' => $action['id'], 'description' => $action['description']];
        }

        $data = [
          'title' => '操作权限管理',
          'menus' => $menus,
          'actions' => $actions
        ];

        return $this->respondView('pages/permission/actions.twig', $data);
      default:
        return $this->respondWithError('禁止访问', 403);
    }
  }
}
