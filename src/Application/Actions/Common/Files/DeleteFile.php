<?php

namespace App\Application\Actions\Common\Files;

use App\Application\Middleware\PermissionMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Service\UploaderService;

class DeleteFile extends Action
{
  public function __construct(private readonly UploaderService $uploader)
  {
  }

  #[Route(
    path: '/file/delete/{id:[0-9]+}',
    methods: ['DELETE'],
    description: '文件删除',
    name: 'app.file.delete',
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    $id = (int)$this->resolveArg('id', 0);
    if ($id > 0) {
      return $this->respondWithData(['delNum' => $this->uploader->delFile($id)], 204);
    } else {
      return $this->respondWithError('缺少ID');
    }
  }
}