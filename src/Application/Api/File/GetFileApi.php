<?php

namespace App\Application\Api\File;

use Exception;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\ResourceServerMiddleware;
use WanPHP\Core\Middleware\ScopeMiddleware;
use WanPHP\Core\Service\UploaderService;

class GetFileApi extends Action
{
  public function __construct(private readonly UploaderService $uploader)
  {
  }

  #[Route(
    path: '/api/file/{id:[0-9]+}',
    methods: ['GET'],
    name: 'api.get.file',
    middleware: [[ScopeMiddleware::class, 'file.read'], ResourceServerMiddleware::class]
  )]
  #[OA\Get(
    path: "/api/file/{id}", // Swagger 路径建议写带参数的版本
    operationId: "getFile",
    summary: "获取单个文件",
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
      new OA\Response(response: 200, description: "请求成功", content: new OA\JsonContent()),
      new OA\Response(response: 404, description: "文件不存在")
    ]
  )]
  protected function action(): Response
  {
    $id = (int)$this->resolveArg('id', 0);
    if ($id > 0) {
      $file = $this->uploader->get(['id' => $id]);
      return $this->respondWithData($file);
    }
    return $this->respondWithError('ID错误');
  }

  #[Route(path: '/api/search_file', methods: ['GET'], name: 'api.search.file', middleware: [ResourceServerMiddleware::class])]
  #[OA\Get(
    path: "/api/search_file",
    operationId: "searchFile",
    summary: "搜索文件",
    tags: ["File"],
    parameters: [
      new OA\Parameter(
        name: "keyword",
        description: "关键词搜索（仅列表模式有效）",
        in: "query",
        required: false,
        schema: new OA\Schema(type: "string")
      )
    ],
    responses: [
      new OA\Response(response: 200, description: "请求成功", content: new OA\JsonContent()),
      new OA\Response(response: 404, description: "请求失败")
    ]
  )]
  public function files(ServerRequestInterface $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;
    $get = $this->request->getQueryParams();
    if (!empty($get['keyword'])) {
      $keyword = trim($get['keyword']);
      $where['name[~]'] = $keyword;
    }
    try {
      $files = $this->uploader->select('id,url,name,type,size,uptime', $where ?? []);
      //格式化数据
      $data = [];
      foreach ($files as $file) {
        $file['uptime'] = date('Y-m-d H:i:s', $file['uptime']);
        $data[] = $file;
      }
      return $this->respondWithData($data);
    } catch (Exception $e) {
      return $this->respondWithError($e->getMessage());
    }
  }
}