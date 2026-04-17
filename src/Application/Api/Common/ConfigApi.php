<?php

namespace App\Application\Api\Common;

use App\Service\Common\SettingService;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Middleware\ResourceServerMiddleware;

class ConfigApi extends Action
{

  public function __construct(private readonly SettingService $setting)
  {
  }

  #[Route(
    path: '/api/get/config/{key:[a-zA-Z0-9_-]+}',
    methods: ['GET'],
    name: 'api.get.config',
    middleware: [ResourceServerMiddleware::class]
  )]
  #[OA\Get(
    path: "/api/get/config/{key}",
    operationId: "getConfig",
    summary: "取自定义配置",
    tags: ["System"],
    parameters: [
      new OA\Parameter(
        name: "key",
        description: "KEY",
        in: "path",
        required: true,
        schema: new OA\Schema(type: "integer")
      )
    ],
    responses: [
      new OA\Response(response: 200, description: "请求成功"),
      new OA\Response(response: 404, description: "请求失败")
    ]
  )]
  protected function action(): Response
  {
    $userid = $this->getUid();
    if ($userid < 1) return $this->respondWithError('未知用户', 422);

    return $this->respondWithData(['value' => $this->setting->getConfig($this->resolveArg('key'))]);
  }
}
