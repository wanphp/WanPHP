<?php

namespace App\Application\Api\File;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\ResourceServerMiddleware;
use WanPHP\Core\Middleware\ScopeMiddleware;
use WanPHP\Core\Service\UploaderService;

class DeleteFileApi extends Action
{
  public function __construct(private readonly UploaderService $uploader)
  {
  }

  #[Route(
    path: '/api/delete/file',
    methods: ['DELETE'],
    name: 'api.delete.file',
    middleware: [[ScopeMiddleware::class, 'file.delete'], ResourceServerMiddleware::class]
  )]
  #[OA\Delete(
    path: "/api/delete/file",
    operationId: "deleteFile",
    summary: "删除文件（参数二选一）",
    security: [["bearerAuth" => ["file.delete"]]],
    tags: ["File"],
    parameters: [
      new OA\Parameter(
        name: "id",
        description: "文件ID",
        in: "query",
        required: false,
        schema: new OA\Schema(type: "integer")
      ),
      new OA\Parameter(
        name: "path",
        description: "文件路径",
        in: "query",
        required: false,
        schema: new OA\Schema(type: "string")
      )
    ],
    responses: [
      new OA\Response(response: 200, description: "删除成功", content: new OA\JsonContent()),
      new OA\Response(response: 400, description: "删除失败")
    ]
  )]
  protected function action(): Response
  {
    $data = $this->getFormData();
    $id = (int)($data['id'] ?? 0);
    if ($id > 0) {
      return $this->respondWithData(['delNum' => $this->uploader->delFile($id)]);
    } else {
      if (!empty($data['path'])) return $this->respondWithData(['delNum' => $this->uploader->delFile($data['path'])]);
      return $this->respondWithError('缺少ID');
    }
  }
}