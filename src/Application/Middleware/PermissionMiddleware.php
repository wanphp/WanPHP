<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/9/7
 * Time: 14:44
 */

namespace App\Application\Middleware;

use App\Service\Admin\AdminService;
use App\Service\Common\PersistenceService;
use BaconQrCode\Writer;
use Exception;
use League\OAuth2\Server\Exception\OAuthServerException;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\SimpleCache\CacheInterface;
use Slim\Psr7\Response as SlimResponse;
use Slim\Routing\RouteContext;
use Slim\Views\Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use WanPHP\Core\Middleware\AdminPermissionMiddlewareInterface;
use WanPHP\Core\Service\UserService;
use WanPHP\Core\Worker\AuditLogContext;

final readonly class PermissionMiddleware implements AdminPermissionMiddlewareInterface
{
  /**
   * @param PersistenceService $persistence
   * @param Writer $writer
   * @param CacheInterface $cache
   * @param AdminService $admin
   * @param UserService $userService
   */
  public function __construct(
    private PersistenceService $persistence,
    private Writer             $writer,
    private CacheInterface     $cache,
    private AdminService       $admin,
    private UserService        $userService
  )
  {
  }

  /**
   * @param Request $request
   * @param RequestHandler $handler
   * @return Response
   * @throws LoaderError
   * @throws RuntimeError
   * @throws SyntaxError
   * @throws Exception
   */
  public function process(Request $request, RequestHandler $handler): Response
  {
    if (isset($_SESSION['login_id']) && is_numeric($_SESSION['login_id'])) {//已登录，验证权限
      $this->persistence->setPermission($_SESSION['role_id']);
      $routeContext = RouteContext::fromRequest($request);

      if ($this->persistence->hasRestricted($routeContext->getRoute()->getCallable())) {
        if ($request->getHeaderLine("X-Requested-With") == "XMLHttpRequest") {
          $response = new SlimResponse();
          $json = json_encode(['errMsg' => '用户未获得授权，操作被拒绝！'], JSON_PRETTY_PRINT);
          $response->getBody()->write($json);
          return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } else {
          return Twig::fromRequest($request)->render(new SlimResponse(), 'admin/error/404.html?loadTpl=1', ['message' => '用户未获得授权！']);
        }
      }
      $view = Twig::fromRequest($request);
      $view->offsetSet('sidebar', $this->persistence->getSidebar());
      // user info
      $loginUser = $this->cache->get('userInfo_' . $_SESSION['login_id']);
      if (empty($loginUser)) {
        $loginUser = $this->admin->getColumn('name,tel,openid', ['id' => $_SESSION['login_id']]);
        if (!empty($loginUser['openid'])) {
          $user = $this->userService->getUser($loginUser['openid']);
          if (!empty($user)) $loginUser = array_merge($user, $loginUser);
        }
        $this->cache->set('userInfo_' . $_SESSION['login_id'], $loginUser);
      }
      // 日志审计
      AuditLogContext::markActor('admin', $_SESSION['login_id']);

      $view->offsetSet('loginUser', $loginUser);
      $view->offsetSet('systemName', getenv('APP_NAME'));
      $view->offsetSet('loginId', $_SESSION['login_id']);
      $view->offsetSet('Role', $_SESSION['role_id']);
      return $handler->handle($request);
    } else {
      // OAuth2.0 验证
      $client_id = $request->getAttribute('oauth_client_id');
      $role_id = $request->getAttribute('oauth_admin_role_id');

      if ($client_id == 'sysManage' && $role_id) {//已登录，验证权限
        $this->persistence->setPermission($role_id);
        $routeContext = RouteContext::fromRequest($request);

        if ($this->persistence->hasRestricted($routeContext->getRoute()->getCallable())) {
          return new OAuthServerException('未获得授权！', 401, 'Unauthorized')->generateHttpResponse(new SlimResponse());
        }
        return $handler->handle($request);
      } else {
        if (isset($_SESSION['login_openid']) && is_numeric($_SESSION['login_openid'])) {
          $openid = $_SESSION['login_openid'];
          unset($_SESSION['login_openid']);
          // 通过公众号被动回复连接授权，恢复会话
          $admin = $this->admin->get(['openid' => $openid]);
          if (isset($admin['status']) && $admin['status'] == 1) {
            $_SESSION['login_id'] = $admin['id'];
            $_SESSION['role_id'] = $admin['role_id'];
            $_SESSION['groupId'] = $admin['groupId'];
            $_SESSION['user_openid'] = $openid;
            $serverParams = $request->getServerParams();
            $ip = $serverParams['HTTP_X_FORWARDED_FOR'] ?? $serverParams['REMOTE_ADDR'];
            $this->admin->updateEntityToArray(['lastLoginTime' => time(), 'lastLoginIp' => $ip], ['id' => $admin['id']]);
            AuditLogContext::markActor(type: 'admin', id: $admin['id']);
            AuditLogContext::markChanged(resource: null, id: null, action: 'login');
            // 获取当前请求的 URL
            $url = $request->getUri()->getPath();
            return $handler->handle($request)->withHeader('Location', $url)->withStatus(302);
          }
        }
        if ($request->getHeaderLine("X-Requested-With") == "XMLHttpRequest") {
          $response = new SlimResponse();
          $json = json_encode(['type' => 'reload', 'errMsg' => '用户未登录或登录超时！']);
          $response->getBody()->write($json);
          return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        } else {
          if (!$this->admin->count()) return $handler->handle($request)->withHeader('Location', $request->getUri()->getScheme() . '://' . $request->getUri()->getHost() . RouteContext::fromRequest($request)->getRouteParser()->urlFor('app.init'))->withStatus(302);
          foreach (glob(ROOT_PATH . '/public/assets/{app-,style-}*.{js,css}', GLOB_BRACE) as $item) {
            $item = str_replace(ROOT_PATH . '/public', '', $item);
            if (str_ends_with($item, '.css')) $data['app_css_path'] = $item;
            else $data['app_js_path'] = $item;
          }
          $userAgent = $request->getHeaderLine('User-Agent');
          $isWechat = str_contains(strtolower($userAgent), 'micromessenger');
          if ($isWechat) {
            $data['redirect_uri'] = RouteContext::fromRequest($request)->getRouteParser()->urlFor('app.qrLogin', [], ['state' => 'weixin']);
          } else {
            $scene = bin2hex(random_bytes(16));
            $this->cache->set($scene, session_id(), 60);
            $baseUrl = RouteContext::fromRequest($request)->getRouteParser()->urlFor('app.qrLogin', [], ['tk' => $scene]);
            $data['loginQr'] = $this->writer->writeString($request->getUri()->getScheme() . '://' . $request->getUri()->getHost() . $baseUrl);
          }
          $data['systemName'] = getenv('APP_NAME');
          return Twig::fromRequest($request)->render(new SlimResponse(), 'pages/home/login.twig', $data);
        }
      }
    }

  }

}
