<?php

namespace App\Application\Actions\Common\Files;

use App\Application\Middleware\PermissionMiddleware;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Service\UploaderService;

class DownloadFile extends Action
{
public function __construct(private readonly UploaderService $uploader)
{
}

  #[Route(
    path: '/download/{id:[0-9]+}',
    methods: ['GET'],
    description: '下载文件',
    name: 'app.file.download',
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    $id = (int)$this->resolveArg('id', 0);

    if ($id > 0) {
      $file = $this->uploader->getDownloadFile($id);
      if (empty($file)) return $this->response->withStatus(404, 'File not found');

      $encoded = rawurlencode($file['name']) . '.' . pathinfo($file['url'], PATHINFO_EXTENSION);

      return $this->response
        ->withHeader('Content-Type', 'application/octet-stream')
        ->withHeader('Content-Disposition', "attachment; filename*=UTF-8''{$encoded}")
        ->withHeader('Cache-Control', 'no-store')
        ->withHeader('X-Accel-Redirect', '/protected-files/' . $file['url']);
    } else {
      return $this->response->withStatus(404);
    }
  }
}