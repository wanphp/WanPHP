<?php

namespace App\Application\Actions\Client;

use App\Application\Middleware\PermissionMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Service\ScopeService;

class AddScope extends Action
{
  use OauthScopeTrait;

  public function __construct(private readonly ScopeService $scope)
  {
  }

  /**
   * @inheritDoc
   */
  #[Route(
    path: '/admin/client/scope/add',
    methods: ['GET', 'POST'],
    description: '添加授权范围',
    name: 'app.client.scope.add',
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    if ($this->isPost()) {
      $data = $this->getFormData();
      $data['id'] = $this->scope->addScope($data);
      return $this->respondWithData($data, 201);
    } else {
      $data = [
        'title' => '授权范围',
        'modalName' => 'app.client.scope.modal',
        'action' => $this->urlFor('app.client.scope.add'),
        'scopes' => $this->oauthScopes()
      ];

      return $this->respondView('pages/client/scope-modal.twig', $data);
    }
  }
}