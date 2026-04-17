<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/9/10
 * Time: 16:43
 */

namespace App\Application\Actions\Login;


use App\Application\Middleware\PermissionMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Worker\AuditLogContext;

class LoginOutAction extends Action
{


  #[Route(path: '/loginOut', methods: ['GET'], description: '退出系统', name: 'app.loginOut', middleware: [PermissionMiddleware::class])]
  protected function action(): Response
  {
    AuditLogContext::markChanged(resource: null, id: null, action: 'loginOut');
    session_unset();
    session_destroy();
    session_start();
    session_regenerate_id(true);
    $loginPath = $this->urlFor('app.login');
    return $this->response->withHeader('Location', $loginPath)->withStatus(302);
  }

}
