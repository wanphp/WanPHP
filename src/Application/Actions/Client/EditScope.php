<?php

namespace App\Application\Actions\Client;

use App\Application\Middleware\PermissionMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Service\ScopeService;

class EditScope extends Action
{
  use OauthScopeTrait;

  public function __construct(private readonly ScopeService $scope)
  {
  }

  /**
   * @inheritDoc
   */
  #[Route(
    path: '/admin/client/scope/edit/{id:[0-9]+}',
    methods: ['GET', 'POST'],
    description: '修改授权范围',
    name: 'app.client.scope.edit',
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    $id = (int)$this->resolveArg('id', 0);
    if ($id > 0) {
      if ($this->isPost()) {
        $data = $this->request->getParsedBody();
        $this->scope->updateScope($id, $data);
        $data['id'] = $id;
        return $this->respondWithData($data);
      } else {
        $scope = $this->scope->load($id);
        $scope['scopes'] = json_decode($scope['scopes']);

        $data = [
          'title' => '授权范围',
          'scope' => $scope,
          'modalName' => 'app.client.scope.modal',
          'action' => $this->urlFor('app.client.scope.edit', ['id' => $id]),
          'scopes' => $this->oauthScopes()
        ];

        return $this->respondView('pages/client/scope-modal.twig', $data);
      }
    } else {
      return $this->respondWithError('ID错误');
    }
  }
}