<?php

namespace App\Application\Actions\Client;

use App\Application\Middleware\PermissionMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Service\ClientService;
use WanPHP\Core\Service\ScopeService;

class AddClient extends Action
{
  public function __construct(
    private readonly ClientService $clientService,
    private readonly ScopeService  $scopeService
  )
  {
  }

  /**
   * @inheritDoc
   */
  #[Route(
    path: '/client/add',
    methods: ['GET', 'POST'],
    description: '添加客户端',
    name: 'app.client.add',
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    if ($this->isPost()) {
      $data = $this->getFormData();
      $client_secret = md5(uniqid(rand(), true));
      $data['client_secret'] = password_hash($client_secret, PASSWORD_BCRYPT);
      $data['client_ip'] = explode(',', $data['client_ip']);
      $this->clientService->addClient($data);
      return $this->respondWithData(['dialog' => ['client_secret' => $client_secret]], 201);
    } else {
      $data = [
        'title' => '添加客户端',
        'action' => $this->urlFor('app.client.add'),
        'modalName' => 'app.client.modal',
        'scopes' => $this->scopeService->getAll()
      ];

      return $this->respondView('pages/client/client-modal.twig', $data);
    }
  }
}