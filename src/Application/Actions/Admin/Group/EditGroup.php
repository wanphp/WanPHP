<?php

namespace App\Application\Actions\Admin\Group;

use App\Application\Middleware\PermissionMiddleware;
use App\Service\Admin\GroupService;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class EditGroup extends Action
{
  public function __construct(private readonly GroupService $groupService)
  {
  }

  /**
   * @inheritDoc
   */
  #[Route(
    path: '/admin/group/edit/{id:[0-9]+}',
    methods: ['GET', 'POST'],
    description: '修改管理员分组',
    name: 'app.admin.group.edit',
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    $id = (int)$this->resolveArg('id', 0);
    if ($id > 0) {
      if ($this->isPost()) {
        $data = $this->getFormData();
        if ($this->groupService->checkGroup($data['name'], $id)) {
          return $this->respondWithData(['message' => '分组已经存在', 'errors' => ['name' => [$data['name'] . '分组重名']]], 422);
        }
        $this->groupService->update($id, $data);
        $data['id'] = $id;
        return $this->respondWithData($data);
      } else {
        $data = [
          'title' => '修改管理员分组',
          'group' => $this->groupService->load($id),
          'action' => $this->urlFor('app.admin.group.edit', ['id' => $id]),
          'modalName' => 'app.admin.group.modal',
        ];
        return $this->respondView('pages/admin/group/group-modal.twig', $data);
      }
    } else {
      return $this->respondWithError('ID错误');
    }
  }
}