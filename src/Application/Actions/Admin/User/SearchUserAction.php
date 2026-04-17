<?php

namespace App\Application\Actions\Admin\User;

use App\Application\Middleware\PermissionMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Service\UserService;

class SearchUserAction extends Action
{

  public function __construct(private readonly UserService $user)
  {
  }

  #[Route(path: '/admin/searchUser', methods: ['GET'], description: '搜索用户', name: 'app.searchUser', middleware: [PermissionMiddleware::class])]
  protected function action(): Response
  {
    $params = $this->request->getQueryParams();
    if (isset($params['q']) && $params['q'] != '') {
      $keyword = trim($params['q']);
    } else {
      return $this->respondWithError('关键词不能为空！');
    }
    return $this->respondWithData($this->user->searchUsers($keyword, intval($params['page'] ?? 1)));
  }
}
