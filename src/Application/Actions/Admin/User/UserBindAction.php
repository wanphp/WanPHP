<?php

namespace App\Application\Actions\Admin\User;

use App\Application\Middleware\PermissionMiddleware;
use App\Service\Admin\AdminService;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\SimpleCache\CacheInterface;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Service\UserService;

class UserBindAction extends Action
{
  public function __construct(
    private readonly UserService      $user,
    private readonly AdminService     $admin,
    private readonly CacheInterface   $cache
  )
  {
  }

  #[Route(path: '/admin/userBind', methods: ['GET', 'POST'], description: '绑定微信', name: 'app.userBind', middleware: [PermissionMiddleware::class])]
  protected function action(): Response
  {
    if ($this->isPost()) {
      $admin_id = $this->getLoginId();
      $bindOpenid = $this->getLoginUserId();
      if ($admin_id > 0) {
        $admin = $this->admin->get(['id' => $admin_id]);
        // 当前用户已绑定微信
        if (!empty($bindOpenid)) {
          // 重新绑定微信
          if (!empty($admin['openid']) && $admin['openid'] != $bindOpenid) {
            $_SESSION['user_openid'] = $admin['openid'];
            $this->cache->delete('userInfo_' . $this->getLoginId());
            return $this->respondWithData(['reload' => true, 'message' => '重新绑定微信成功']);
          }
          // 解除微信绑定
          if (empty($admin['openid'])) {
            unset($_SESSION['user_openid']);
            $this->cache->delete('userInfo_' . $this->getLoginId());
            return $this->respondWithData(['reload' => true, 'message' => '解除微信绑定成功']);
          }
        } else {
          // 绑定微信
          if (!empty($_SESSION['login_openid'])) {
            $openid = $_SESSION['login_openid'];
            unset($_SESSION['login_openid']);
            $_SESSION['user_openid'] = $openid;
            $this->cache->delete('userInfo_' . $this->getLoginId());
            return $this->respondWithData(['reload' => true, 'message' => '微信绑定成功']);
          }
        }
        return $this->respondWithData(['尚未授权！']);
      } else {
        return $this->respondWithError('未知用户！');
      }
    } else {
      $queryParams = $this->request->getQueryParams();
      if (isset($queryParams['code'])) {//微信公众号认证回调
        $user = $this->user->getOauthUserinfo($queryParams['code']);
        // 检查绑定管理员
        $admin_id = $this->getLoginId();
        if (!empty($user['openid']) && $admin_id > 0) {
          $admin = $this->admin->get(['openid' => $user['openid']]);
          if ($admin) {
            if ($admin['id'] == $admin_id) {
              return $this->respondWxMsg('warn', '系统提醒', '重复绑定，您应该使用新的微信扫码');
            } else {
              return $this->respondWxMsg('warn', '系统提醒', '您的微信已与”' . $admin['account'] . '“帐号绑定，需先解除才能绑定！！');
            }
          } else {
            $account = $this->admin->getColumn('account', ['id' => $admin_id]);
            // 扫码微信未绑定过
            $data = ['openid' => $user['openid'], 'name' => $user['name']];
            if ($user['tel']) $data['tel'] = $user['tel'];
            $up = $this->admin->updateEntityToArray($data, ['id' => $admin_id]);
            if ($up > 0) {
              $this->cache->delete('userInfo_' . $admin_id);
              // 记录扫码微信uid，登录登录帐号uid为$_SESSION['user_id']，注意区分
              $_SESSION['login_openid'] = $user['openid'];

              return $this->respondWxMsg('success', '绑定成功', '您的帐号“' . $account . '”已成功与您的微信绑定！');
            } else {
              return $this->respondWxMsg('warn', '系统提醒', '绑定失败，请重试！！');
            }
          }
        } else {
          return $this->respondWxMsg('warn', '系统提醒', '未知用户，帐号绑定失败！！');
        }
      } else {
        return $this->user->oauthRedirect($this->request, $this->response);
      }
    }
  }

  /**
   * 解绑微信
   * @throws Exception
   */
  #[Route(path: '/admin/userUnBind', methods: ['GET'], description: '解绑微信', name: 'app.userUnBind', middleware: [PermissionMiddleware::class])]
  public function unBind(Request $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;

    $queryParams = $this->request->getQueryParams();
    if (isset($queryParams['code'])) {//微信公众号认证回调
      $user = $this->user->getOauthUserinfo($queryParams['code']);
      // 检查绑定管理员
      $admin_id = $this->getLoginId();
      $bindOpenid = $this->getLoginUserId();
      if (!empty($user['openid']) && $admin_id > 0 && $bindOpenid == $user['openid']) {
        $admin = $this->admin->get(['openid' => $user['openid']]);
        if ($admin) {
          $data = ['openid' => null];
          $this->admin->updateEntityToArray($data, ['id' => $admin_id]);
          return $this->respondWxMsg('success', '解除绑定成功', '您的微信已与”' . $admin['account'] . '“帐号成功解除绑定，可以绑定到其它账号！！');
        } else {
          return $this->respondWxMsg('warn', '系统提醒', '重复解绑操作，您的微信当前未绑定此账号！！');
        }
      } else {
        return $this->respondWxMsg('warn', '系统提醒', '绑定帐号与当前授权用户不是一个用户！！');
      }
    } else {
      return $this->user->oauthRedirect($this->request, $this->response);
    }
  }
}
