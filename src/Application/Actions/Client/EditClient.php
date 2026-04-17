<?php

namespace App\Application\Actions\Client;

use App\Application\Middleware\PermissionMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Service\ClientService;
use WanPHP\Core\Service\ScopeService;

class EditClient extends Action
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
  #[Route(path: '/client/edit/{id:[0-9]+}', methods: ['GET', 'POST'], description: '修改客户端', name: 'app.client.edit', middleware: [PermissionMiddleware::class])]
  protected function action(): Response
  {
    $id = (int)$this->resolveArg('id', 0);
    if ($id > 0) {
      if ($this->isPost()) {
        $data = $this->getFormData();
        $data['client_ip'] = explode(',', $data['client_ip']);
        $num = $this->clientService->updateClient($id, $data);
        return $this->respondWithData(['upNum' => $num]);
      } else {
        $client = $this->clientService->getClient($id);

        $data = [
          'title' => '修改客户端',
          'client' => $client,
          'action' => $this->urlFor('app.client.edit', ['id' => $id]),
          'modalName' => 'app.client.modal',
          'scopes' => $this->scopeService->getAll()
        ];

        return $this->respondView('pages/client/client-modal.twig', $data);
      }
    } else {
      return $this->respondWithError('ID错误');
    }
  }
}