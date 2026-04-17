<?php

namespace App\Application\Actions\Common\Files;

use App\Application\Middleware\PermissionMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Service\UploaderService;

class EditFileName extends Action
{
public function __construct(private readonly UploaderService $uploader)
{
}

  #[Route(
    path: '/edit/filename/{id:[0-9]+}',
    methods: ['PATCH'],
    description: '修改文件名文件',
    name: 'app.file.edit',
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    $data = $this->getFormData();
    $id = (int)$this->resolveArg('id', 0);
    if ($id > 0 && !empty($data['name'])) {
      $num = $this->uploader->setName($id, $data['name']);
      return $this->respondWithData(['upNum' => $num]);
    } else {
      return $this->respondWithError('修改失败！');
    }
  }
}