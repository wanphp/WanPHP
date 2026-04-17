<?php

namespace App\Application\Actions\Common\Navigate;

use App\Application\Middleware\PermissionMiddleware;
use App\Service\Common\NavigateService;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class DeleteNavigate extends Action
{
  public function __construct(private readonly NavigateService $navigateService)
  {
  }

  #[Route(
    path: '/navigate/delete/{id:[0-9]+}',
    methods: ['DELETE'],
    description: '删除系统导航',
    name: 'app.navigate.delete',
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    $id = (int)$this->resolveArg('id', 0);
    if ($id > 0) {
      $delNum = $this->navigateService->delete($id);
      return $this->respondWithData(['delNum' => $delNum]);
    } else {
      return $this->respondWithError('ID有误');
    }
  }
}