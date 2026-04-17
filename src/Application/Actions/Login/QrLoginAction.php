<?php

namespace App\Application\Actions\Login;

use App\Service\Admin\AdminService;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Service\UserService;
use WanPHP\Core\Worker\AuditLogContext;

class QrLoginAction extends Action
{
  /**
   * @throws Exception
   */
  public function __construct(private readonly UserService $user, private readonly AdminService $admin)
  {
  }

  #[Route(path: '/qrLogin', methods: ['GET', 'POST'], description: '微信授权登录', name: 'app.qrLogin')]
  protected function action(): Response
  {
    if ($this->isPost()) {
      if ($this->getLoginId()) {
        AuditLogContext::markActor(type: 'admin', id: $this->getLoginId());
        AuditLogContext::markChanged(resource: null, id: null, action: 'login');
        $redirect = $_SESSION['redirect_uri'] ?? $this->httpHost() . $this->urlFor('app.dashboard');
        return $this->respondWithData(['redirect' => $redirect]);
      } else if (isset($_SESSION['login_openid']) && is_numeric($_SESSION['login_openid'])) {
        // 没有网页授权获取用户基本信息，通过公众号被动回复连接授权登录
        $openid = $_SESSION['login_openid'];
        unset($_SESSION['login_openid']);
        $admin = $this->admin->get(['openid' => $openid]);
        if (!$admin) {
          return $this->respondWithData(['reload' => true, 'message' => '微信尚未绑定帐号，请使用密码登录！'], 400);
        }
        if ($admin['status']) {
          $_SESSION['login_id'] = $admin['id'];
          $_SESSION['role_id'] = $admin['role_id'];
          $_SESSION['groupId'] = $admin['groupId'];
          $_SESSION['user_openid'] = $openid;
          $this->admin->updateEntityToArray(['lastLoginTime' => time(), 'lastLoginIp' => $this->getIP()], ['id' => $admin['id']]);
          $redirect = $_SESSION['redirect_uri'] ?? $this->httpHost() . $this->urlFor('app.dashboard');
          return $this->respondWithData(['redirect' => $redirect]);
        } else {
          return $this->respondWithData(['reload' => true, 'message' => '帐号已被锁定，无法登录！！'], 400);
        }
      } else return $this->respondWithData(['尚未授权！']);
    } else {
      $queryParams = $this->request->getQueryParams();
      $state = $queryParams['state'] ?? '';
      if (isset($queryParams['code'])) {//微信公众号认证回调
        $user = $this->user->getOauthUserinfo($queryParams['code']);
        // 检查绑定管理员
        if (!empty($user['openid'])) {
          $admin = $this->admin->get(['openid' => $user['openid']]);
          if (!$admin) {
            return $this->respondWxMsg('warn', '系统提醒', '微信尚未绑定帐号，请使用密码登录！');
          }
          if ($admin['status']) {
            $_SESSION['login_id'] = $admin['id'];
            $_SESSION['role_id'] = $admin['role_id'];
            $_SESSION['groupId'] = $admin['groupId'];
            $_SESSION['user_openid'] = $user['openid'];
            $this->admin->updateEntityToArray(['lastLoginTime' => time(), 'lastLoginIp' => $this->getIP()], ['id' => $admin['id']]);
            if ($state == 'weixin') {
              AuditLogContext::markActor(type: 'admin', id: $this->getLoginId());
              AuditLogContext::markChanged(resource: null, id: null, action: 'login');
              $backUrl = $_SESSION['redirect_uri'] ?? $this->httpHost() . $this->urlFor('app.dashboard');
              return $this->response->withHeader('Location', $backUrl)->withStatus(302);
            } else {
              return $this->respondWxMsg('success', '登录成功', '您已成功授权，详情查看PC端扫码页面！');
            }
          } else {
            return $this->respondWxMsg('warn', '系统提醒', '帐号已被锁定，无法登录！！');
          }
        } else {
          return $this->respondWithError('未知用户！');
        }
      } else {
        // 记录当前URI
        $redirect_uri = $this->request->getHeaderLine('Referer');
        if (!str_contains($redirect_uri, '/login')) $_SESSION['redirect_uri'] = $redirect_uri;
        return $this->user->oauthRedirect($this->request, $this->response);
      }
    }
  }
}
