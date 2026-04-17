<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/9/25
 * Time: 10:49
 */

namespace App\Application\Api;

use App\Application\Middleware\DocAuthMiddleware;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use OpenApi\Generator;
use OpenApi\Attributes as OA;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use WanPHP\Core\Attribute\Route;

/**
 * 这是生成 OpenApi/Swagger 文档的控制器。
 * 应用程序的根 OpenApi 定义放在这里，以便扫描器找到它。
 */
#[OA\OpenApi(
  info: new OA\Info(
    version: "1.0.0",
    description: "系统 API 服务文档",
    title: "System API"
  ),

  servers: [
    new OA\Server(url: "/", description: "当前服务器")
  ],

  security: [["bearerAuth" => []]],

  tags: [
    new OA\Tag(name: "Auth", description: "认证授权"),
    new OA\Tag(name: "System", description: "系统管理"),
    new OA\Tag(name: "UserRole", description: "用户角色"),
    new OA\Tag(name: "User", description: "用户管理"),
    new OA\Tag(name: "Wechat", description: "微信接口"),
    new OA\Tag(name: "File", description: "文件管理"),
    new OA\Tag(name: "News", description: "新闻同步接口"),
    new OA\Tag(name: "Integral", description: "用户积分接口"),
    new OA\Tag(name: "NewsPaper", description: "报纸版面"),
  ],

  components: new OA\Components(
    schemas: [
      new OA\Schema(
        schema: "Success",
        title: "成功响应结构",
        properties: [
          new OA\Property(property: "message", description: "提示信息", type: "string", example: "请求成功", nullable: true),
          new OA\Property(property: "dialog", description: "对话框信息", type: "object", nullable: true),
          new OA\Property(property: "data", description: "返回数据", type: "object", nullable: true, additionalProperties: true)
        ],
        type: "object"
      ),

      new OA\Schema(
        schema: "Error",
        title: "错误响应结构",
        properties: [
          new OA\Property(property: "message", description: "提示信息", type: "string", example: "请求失败"),
          new OA\Property(
            property: "errors",
            description: "表单验证错误信息",
            type: "object",
            nullable: true,
            additionalProperties: new OA\AdditionalProperties(
              type: "array",
              items: new OA\Items(type: "string")
            )
          )
        ],
        type: "object"
      )
    ],

    responses: [
      new OA\Response(
        response: "res200",
        description: "请求成功",
        content: new OA\JsonContent(ref: "#/components/schemas/Success")
      ),
      new OA\Response(
        response: "res201",
        description: "资源创建成功",
        content: new OA\JsonContent(ref: "#/components/schemas/Success")
      ),
      new OA\Response(
        response: "res204",
        description: "资源删除成功，无返回内容"
      ),
      new OA\Response(
        response: "error",
        description: "请求错误",
        content: new OA\JsonContent(ref: "#/components/schemas/Error")
      ),
    ],

    securitySchemes: [
      new OA\SecurityScheme(
        securityScheme: "bearerAuth",
        type: "http",
        bearerFormat: "JWT",
        scheme: "Bearer"
      )
    ]
  )
)]
readonly class SwaggerController
{
  public function __construct(private CacheInterface $cache)
  {
  }

  #[Route(
    path: "/api/docs.json",
    methods: ["GET"],
    description: "获取 OpenAPI 规格的 JSON 文件",
    name: "api.docs.json",
    middleware: [DocAuthMiddleware::class]
  )]
  public function generateOpenApiJson(Request $request, Response $response): Response
  {
    try {
      $json = $this->cache->get('openapi.json');
      if (!$json) {
        $generator = new Generator();
        $paths = [
          ROOT_PATH . '/src/Application/Api',
          ROOT_PATH . '/src/Entities',
          ROOT_PATH . '/vendor/wanphp//core/src/AuthAction'
        ];

        $apiPath = glob(ROOT_PATH . '/wanphp/plugins/*/src/Application/Api', GLOB_ONLYDIR);
        if ($apiPath) $paths = array_merge($paths, $apiPath);
        $openApi = $generator->generate($paths);

        // 输出 JSON 字符串
        $json = $openApi->toJson();
        $this->cache->set('openapi.json', $json, 3600);
      }
      $response->getBody()->write($json);

      return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (Exception|InvalidArgumentException $e) {
      error_log("Swagger generation error: " . $e->getMessage());
      $response->getBody()->write(json_encode([
        'error' => '无法生成 Swagger 文档。',
        'message' => $e->getMessage()
      ]));
      return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
  }

  /**
   * 显示 Redoc UI 页面。
   */
  #[Route(
    path: "/docs",
    methods: ["GET", "POST"],
    description: "显示 Swagger UI 接口文档",
    name: "api.docs.ui",
    middleware: [DocAuthMiddleware::class]
  )]
  public function showRedocUI(Request $request, Response $response): Response
  {
    $htmlContent = <<<HTML
<!DOCTYPE html>
<html lang="zh">
  <link rel="shortcut icon" href="/assets/images/logo.png">
<head>
    <meta charset="UTF-8">
    <title>API Documentation (Redoc)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body { 
            margin: 0; 
            padding: 0; 
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif;
            background-color: #f4f6f8;
        }
    </style>
</head>
<body>
    <redoc spec-url="/api/docs.json" hide-hostname lazy-rendering></redoc>
    <script src="/assets/js/bundles_redoc.standalone.js"></script>
</body>
</html>
HTML;
    $response->getBody()->write($htmlContent);
    return $response->withHeader('Content-Type', 'text/html')->withStatus(200);
  }
}

