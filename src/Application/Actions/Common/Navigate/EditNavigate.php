<?php

namespace App\Application\Actions\Common\Navigate;

use App\Application\Middleware\PermissionMiddleware;
use App\Service\Common\NavigateService;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class EditNavigate extends Action
{
  public function __construct(private readonly NavigateService $navigateService)
  {
  }

  #[Route(
    path: '/navigate/edit/{id:[0-9]+}',
    methods: ['GET', 'POST'],
    description: '修改系统导航',
    name: 'app.navigate.edit',
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    $id = (int)$this->resolveArg('id', 0);
    if ($id > 0) {
      if ($this->isPost()) {
        $data = $this->getFormData();
        if ($this->navigateService->checkNav($data['name'], $id)) return $this->respondWithError('导航菜单已经存在');
        $num = $this->navigateService->update($id, $data);
        return $this->respondWithData(['upNum' => $num, 'message' => '修改成功']);
      } else {
        $data = [
          'title' => '修改系统导航',
          'navigate' => $this->navigateService->load($id),
          'action' => $this->urlFor('app.navigate.edit', ['id' => $id]),
          'modalName' => 'app.navigate.modal',
        ];

        return $this->respondView('pages/permission/navigate-modal.twig', $data);
      }
    } else {
      return $this->respondWithError('ID有误');
    }
  }
}