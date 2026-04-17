<?php

namespace App\Application\Api\File;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\ResourceServerMiddleware;
use WanPHP\Core\Middleware\ScopeMiddleware;
use WanPHP\Core\Service\UploaderService;

class DownloadFile extends Action
{
  public function __construct(private readonly UploaderService $uploader)
  {
  }

  #[Route(
    path: '/api/download/{id:[0-9]+}',
    methods: ['GET'],
    name: 'api.download.file',
    middleware: [[ScopeMiddleware::class, 'file.read'], ResourceServerMiddleware::class]
  )]
  #[OA\Get(
    path: "/api/download/{id}",
    operationId: "downloadFile",
    summary: "下载文件",
    security: [["bearerAuth" => ["file.read"]]],
    tags: ["File"],
    parameters: [
      new OA\Parameter(
        name: "id",
        description: "文件ID",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
      )
    ],
    responses: [
      new OA\Response(response: 200, description: "请求成功"),
      new OA\Response(response: 404, description: "文件不存在")
    ]
  )]
  protected function action(): Response
  {
    $id = $this->resolveArg('id', 0);
    if ($id <= 0) {
      return $this->response->withStatus(404);
    }

    $file = $this->uploader->getDownloadFile($id);
    if (empty($file)) return $this->response->withStatus(404);

    $encoded = rawurlencode($file['name']);

    return $this->response
      ->withHeader('Content-Type', 'application/octet-stream')
      ->withHeader('Content-Disposition', "attachment; filename*=UTF-8''{$encoded}")
      ->withHeader('Cache-Control', 'no-store')
      ->withHeader('X-Accel-Redirect', '/protected-files/' . $file['url']);
  }
}