<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/9/3
 * Time: 14:45
 */

namespace App\Application\Actions\Home;


use App\Application\Middleware\PermissionMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class HomeAction extends Action
{

  #[Route(path: '/', methods: ['GET'], description: '首页', name: 'app.home', middleware: [PermissionMiddleware::class])]
  protected function action(): Response
  {
    return $this->response->withHeader('Location', $this->urlFor('app.dashboard'))->withStatus(302);
  }

}
