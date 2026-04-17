<?php

namespace App\Application\Actions\Admin\Group;

use App\Application\Middleware\PermissionMiddleware;
use App\Service\Admin\GroupService;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class AddGroup extends Action
{
  public function __construct(private readonly GroupService $groupService)
  {
  }

  /**
   * @inheritDoc
   */
  #[Route(
    path: '/admin/group/add',
    methods: ['GET', 'POST'],
    description: '添加管理员分组',
    name: 'app.admin.group.add',
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    if ($this->isPost()) {
      $data = $this->getFormData();
      if ($this->groupService->checkGroup($data['name'])) {
        return $this->respondWithData(['message' => '分组已经存在', 'errors' => ['name' => [$data['name'] . '分组重名']]], 422);
      }
      $data['id'] = $this->groupService->save($data);
      return $this->respondWithData($data, 201);
    } else {
      $data = [
        'title' => '管理员分组',
        'group' => $group ?? [],
        'action' => $this->urlFor('app.admin.group.add'),
        'modalName' => 'app.admin.group.modal',
      ];

      return $this->respondView('pages/admin/group/group-modal.twig', $data);
    }

  }
}