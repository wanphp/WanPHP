<?php

namespace App\Application\Actions\Admin\Group;


use App\Application\Middleware\PermissionMiddleware;
use App\Service\Admin\GroupService;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class GroupAction extends Action
{

  public function __construct(private readonly GroupService $group)
  {
  }

  #[Route(
    path: '/admin/group',
    methods: ['GET'],
    description: '管理员分组管理',
    name: 'app.admin.group',
    isNav: true,
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    if ($this->isAjax()) {
      $data = [];
      foreach ($this->group->select('id,name,description,displayOrder') as $group) {
        $group['actions'] = [
          'edit' => [
            'name' => '修改',
            'path' => $this->urlFor('app.admin.group.edit', ['id' => $group['id']]),
            'modal' => ['name' => 'app.admin.group.modal']
          ],
          'delete' => [
            'name' => '删除',
            'path' => $this->urlFor('app.admin.group.delete', ['id' => $group['id']]),
            'message' => '是否确认要删除分组'
          ]
        ];
        $data[] = $group;
      }
      return $this->respondWithData([
        'data' => $data
      ]);
    } else {
      $data = [
        'title' => '管理员分组管理'
      ];

      return $this->respondView('pages/admin/group/index.twig', $data);
    }
  }
}
