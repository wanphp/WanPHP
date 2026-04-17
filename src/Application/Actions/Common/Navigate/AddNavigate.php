<?php

namespace App\Application\Actions\Common\Navigate;

use App\Application\Middleware\PermissionMiddleware;
use App\Service\Common\NavigateService;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class AddNavigate extends Action
{
  public function __construct(private readonly NavigateService $navigateService)
  {
  }

  #[Route(
    path: '/navigate/add',
    methods: ['GET', 'POST'],
    description: '系统导航配置',
    name: 'app.navigate.add',
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    if ($this->isPost()) {
      $data = $this->getFormData();
      if ($this->navigateService->checkNav($data['name'])) return $this->respondWithError('导航菜单已经存在');
      $id = $this->navigateService->save($data);
      return $this->respondWithData(['id' => $id, 'message' => '添加成功'], 201);
    } else {
      $data = [
        'title' => '导航菜单配置',
        'action' => $this->urlFor('app.navigate.add'),
        'modalName' => 'app.navigate.modal',
      ];

      return $this->respondView('pages/permission/navigate-modal.twig', $data);
    }
  }
}