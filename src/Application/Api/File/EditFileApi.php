<?php

namespace App\Application\Api\File;

use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\ResourceServerMiddleware;
use WanPHP\Core\Middleware\ScopeMiddleware;
use WanPHP\Core\Service\UploaderService;

class EditFileApi extends Action
{
  public function __construct(private readonly UploaderService $uploader)
  {
  }

  #[Route(
    path: '/api/edit/filename/{id:[0-9]+}',
    methods: ['PATCH'],
    name: 'api.edit.filename',
    middleware: [[ScopeMiddleware::class, 'file.write'], ResourceServerMiddleware::class]
  )]
  #[OA\Patch(
    path: "/api/edit/filename/{id}",
    operationId: "editFile",
    summary: "修改上传文件名",
    security: [["bearerAuth" => ["file.write"]]],
    requestBody: new OA\RequestBody(
      description: "修改上传文件名",
      required: true,
      content: new OA\JsonContent(
        title: "File",
        required: ["name"],
        properties: [
          new OA\Property(property: "name", description: "文件名", type: "string")
        ]
      )
    ),
    tags: ["File"],
    parameters: [
      new OA\Parameter(name: "id", description: "文件ID", in: "path", required: true,
        schema: new OA\Schema(type: "integer", format: "int64")
      )
    ],
    responses: [
      new OA\Response(response: 200, description: "修改成功", content: new OA\JsonContent()),
      new OA\Response(response: 400, description: "修改失败")
    ]
  )]
  protected function action(): Response
  {
    $data = $this->request->getParsedBody();
    $id = (int)$this->resolveArg('id', 0);
    if ($id > 0 && isset($data['name'])) {
      $num = $this->uploader->setName($id, $data['name']);
      return $this->respondWithData(['upNum' => $num]);
    } else {
      return $this->respondWithError('缺少参数', 422);
    }
  }
}