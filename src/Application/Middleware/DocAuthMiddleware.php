<?php
declare(strict_types=1);

namespace App\Application\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;
use Slim\Routing\RouteContext;

class DocAuthMiddleware implements MiddlewareInterface
{
  private const string AUTH_COOKIE_NAME = 'docs_auth_token';

  private string $secretPassword;
  private string $validToken;

  // 通过构造函数接收 DI 注入的配置值
  public function __construct(string $secretPassword, string $validToken)
  {
    $this->secretPassword = $secretPassword;
    $this->validToken = $validToken;
  }

  /**
   * 检查请求是否包含有效的认证 Cookie。
   */
  private function isAuthenticated(Request $request): bool
  {
    $cookies = $request->getCookieParams();
    return isset($cookies[self::AUTH_COOKIE_NAME]) && $cookies[self::AUTH_COOKIE_NAME] === $this->validToken;
  }

  /**
   * 渲染 HTML 登录表单。
   */
  private function renderLoginForm(bool $hasError = false): Response
  {
    $response = new SlimResponse();
    $errorHtml = $hasError ? '<p class="error">❌ 密码错误，请重试。</p>' : '';

    $html = <<<HTML
<!DOCTYPE html>
<html lang="zh">
<head>
    <meta charset="UTF-8">
    <title>文档登录</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            background-color: #f5f5f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
        }
        .login-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            width: 300px;
            text-align: center;
        }
        h2 {
            color: #333;
            margin-bottom: 25px;
        }
        input[type="password"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        button {
            width: 100%;
            padding: 10px;
            background-color: #1d4ed8;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            transition: background-color 0.2s;
        }
        button:hover {
            background-color: #1e40af;
        }
        .error {
            color: #ef4444;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>API 文档访问</h2>
        {$errorHtml}
        <!-- 登录表单提交到新的 POST 路由 -->
        <form action="/docs" method="POST">
            <input type="password" name="password" placeholder="请输入密码" required>
            <button type="submit">登 录</button>
        </form>
    </div>
</body>
</html>
HTML;
    $response->getBody()->write($html);
    return $response->withStatus(401)->withHeader('Content-Type', 'text/html');
  }

  /**
   * @param Request $request
   * @param RequestHandler $handler
   * @return Response
   */
  public function process(Request $request, RequestHandler $handler): Response
  {
    if ($this->isAuthenticated($request)) {
      // 鉴权通过，继续处理请求
      return $handler->handle($request);
    }

    $response = new SlimResponse();
    $uriPath = $request->getUri()->getPath();
    $docUrl = RouteContext::fromRequest($request)->getRouteParser()->urlFor('api.docs.ui');
    $docJson = RouteContext::fromRequest($request)->getRouteParser()->urlFor('api.docs.json');
    // 1. 如果请求的是文档 UI 页面 (/docs)
    if ($request->getUri()->getPath() === $docUrl) {
      if ($request->getMethod() === 'POST') {
        $parsedBody = $request->getParsedBody();
        $password = $parsedBody['password'] ?? '';
        // 使用注入的密码进行校验
        if (!empty($this->secretPassword) && $password === $this->secretPassword) {
          // 设置一个包含有效 Token 的 Cookie，有效期 1 小时 (3600 秒)
          $expires = time() + 3600;
          // 确保 Path, HttpOnly, Secure 设置正确
          $cookieHeader = self::AUTH_COOKIE_NAME . '=' . $this->validToken . '; Path=/; Expires=' . gmdate('D, d M Y H:i:s T', $expires) . '; HttpOnly; Secure; SameSite=Lax';

          return $response->withStatus(302)->withHeader('Location', $docUrl)->withHeader('Set-Cookie', $cookieHeader);
        } else {
          // 密码错误: 重定向回 /docs 并附带错误参数
          return $response->withStatus(302)->withHeader('Location', $docUrl . '?error=1');
        }
      }
      // 检查是否有错误参数，渲染带错误提示的表单
      $queryParams = $request->getQueryParams();
      $hasError = isset($queryParams['error']) && $queryParams['error'] === '1';

      return $this->renderLoginForm($hasError);
    }

    // 2. 如果请求的是文档 JSON 文件 (/api/docs.json)
    if ($uriPath === $docJson) {
      $response->getBody()->write(json_encode([
        'error' => 'Unauthorized',
        'message' => '您没有权限访问 API 文档。请先登录。'
      ]));
      return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
    }

    // 其他情况（理论上不应该，但作为安全措施）
    $response->getBody()->write('Unauthorized Access.');
    return $response->withStatus(401)->withHeader('Content-Type', 'text/plain');
  }
}