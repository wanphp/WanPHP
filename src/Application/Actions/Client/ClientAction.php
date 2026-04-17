<?php

namespace App\Application\Actions\Client;

use App\Application\Middleware\PermissionMiddleware;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Service\ClientService;

class ClientAction extends Action
{
  /**
   * @param ClientService $client
   */
  public function __construct(private readonly ClientService $client)
  {
  }

  #[Route(path: '/admin/client', methods: ['GET'], description: '客户端管理', name: 'app.client', isNav: true, middleware: [PermissionMiddleware::class])]
  protected function action(): Response
  {

    $data = [
      'title' => '客户端管理',
      'clients' => $this->client->getAll()
    ];

    return $this->respondView('pages/client/index.twig', $data);
  }

  /**
   * @throws Exception
   */
  #[Route(
    path: '/client/reset-secret/{id:[0-9]+}',
    methods: ['PATCH'],
    description: '重置密钥',
    name: 'app.client.resetSecret',
    middleware: [PermissionMiddleware::class]
  )]
  public function resetSecret(ServerRequestInterface $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;

    $id = (int)$this->resolveArg('id', 0);
    if ($id > 0) {
      $client_secret = md5(uniqid(rand(), true));
      $this->client->resetSecret($id, $client_secret);
      return $this->respondWithData(['dialog' => ['client_secret' => $client_secret]]);
    } else {
      return $this->respondWithError('ID错误');
    }
  }
}
