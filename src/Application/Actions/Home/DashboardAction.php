<?php

namespace App\Application\Actions\Home;

use App\Application\Middleware\PermissionMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class DashboardAction extends Action
{

  #[Route(path: '/admin/dashboard', methods: ['GET'], description: '仪表盘', name: 'app.dashboard', middleware: [PermissionMiddleware::class])]
  protected function action(): Response
  {
    return $this->respondView('pages/home/dashboard.twig');
  }
}
