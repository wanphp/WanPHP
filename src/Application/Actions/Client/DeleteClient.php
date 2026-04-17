<?php

namespace App\Application\Actions\Client;

use App\Application\Middleware\PermissionMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Service\ClientService;

class DeleteClient extends Action
{
  public function __construct(private readonly ClientService $clientService)
  {
  }

  /**
   * @inheritDoc
   */
  #[Route(path: '/client/delete/{id:[0-9]+}', methods: ['DELETE'], description: '删除客户端', name: 'app.client.delete', middleware: [PermissionMiddleware::class])]
  protected function action(): Response
  {
    $id = (int)$this->resolveArg('id', 0);
    if ($id > 0) {
      $delNum = $this->clientService->deleteClient($id);
      if ($delNum > 0) {
        return $this->respondWithData(['delNum' => $delNum, 'message' => '客户端删除成功'], 204);
      } else {
        return $this->respondWithError('客户端删除失败');
      }
    } else {
      return $this->respondWithError('ID有误');
    }
  }
}