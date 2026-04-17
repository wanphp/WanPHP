<?php

namespace App\Application\Actions\Client;

use App\Application\Middleware\PermissionMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Service\ScopeService;

class DeleteScope extends Action
{

  public function __construct(private readonly ScopeService $scope)
  {
  }

  /**
   * @inheritDoc
   */
  #[Route(
    path: '/admin/client/scope/{id:[0-9]+}',
    methods: ['DELETE'],
    description: '删除授权范围',
    name: 'app.client.scope.delete',
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    $id = (int)$this->resolveArg('id', 0);
    if ($id > 0) {
      $delNum = $this->scope->deleteScope($id);
      if ($delNum > 0) {
        return $this->respondWithData(['delNum' => $delNum, 'message' => '授权范围删除成功'], 204);
      } else {
        return $this->respondWithError('授权范围删除失败');
      }
    } else {
      return $this->respondWithError('ID有误');
    }
  }
}