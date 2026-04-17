<?php

namespace App\Application\Actions\Client;

use App\Application\Middleware\PermissionMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Service\ScopeService;

class ScopeAction extends Action
{

  public function __construct(private readonly ScopeService $scope)
  {
  }

  /**
   * @inheritDoc
   */
  #[Route(
    path: '/admin/client/scope',
    methods: ['GET'],
    description: '客户端授权管理',
    name: 'app.client.scope',
    isNav: true,
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    $data = [
      'title' => '客户端授权范围管理',
      'scopes' => $this->scope->getAll()
    ];

    return $this->respondView('pages/client/scope.twig', $data);
  }
}