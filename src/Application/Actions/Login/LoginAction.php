<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/9/3
 * Time: 16:52
 */

namespace App\Application\Actions\Login;


use App\Application\Common\Message\Message;
use App\Entities\Admin\AdminEntity;
use App\Service\Admin\AdminService;
use BaconQrCode\Writer;
use DeviceDetector\DeviceDetector;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\SimpleCache\CacheInterface;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Worker\AuditLogContext;

class LoginAction extends Action
{

  /**
   * @param AdminService $admin
   * @param Writer $writer
   * @param CacheInterface $cache
   * @param Message $message
   */
  public function __construct(
    private readonly AdminService   $admin,
    private readonly Writer         $writer,
    private readonly CacheInterface $cache,
    private readonly Message        $message
  )
  {
  }

  #[Route(path: '/login', methods: ['GET', 'POST'], description: '登录系统', name: 'app.login')]
  protected function action(): Response
  {
    if ($this->isPost()) {
      //获取数据
      $post = $this->request->getParsedBody();
      $account = trim($post['account'] ?? '');
      $password = trim($post['password'] ?? '');

      if (empty($account)) return $this->respondWithError('请输入用户名！');
      if (empty($password)) return $this->respondWithError('请输入密码！');

      $result = $this->admin->userLogin($account, $password);

      if (!empty($result['msg'])) {
        $this->admin->updateEntityToArray(['lastLoginTime' => time(), 'lastLoginIp' => $this->getIP()], ['id' => $result['id']]);
        if (!empty($result['openid'])) {
          $userAgent = $this->request->getServerParams()['HTTP_USER_AGENT'] ?? '';
          $dd = new DeviceDetector($userAgent);

          // 开始解析
          $dd->parse();

          // 获取操作系统详情
          $osInfo = $dd->getOs();
          // 结果示例: ['name' => 'Windows', 'version' => '10', 'platform' => 'x64']
          $os = $osInfo['name'] . $osInfo['version'];

          // 获取浏览器详情
          $clientInfo = $dd->getClient();
          // 结果示例: ['name' => 'Chrome', 'version' => '120.0', 'engine' => 'Blink'...]
          $browser = $clientInfo['name'] . $clientInfo['version'];

          $scene = bin2hex(random_bytes(16));
          $this->cache->set($scene, session_id(), 60);
          $this->message->login([
            'account' => $account,
            'device' => substr($os . $browser, 0, 20),
            'ip' => $this->getIP(),
            'url' => $this->httpHost() . $this->urlFor('app.dashboard', [], ['tk' => $scene])
          ])->send([$result['openid']]);
        }
        AuditLogContext::markActor(type: 'admin', id: $this->getLoginId());
        AuditLogContext::markChanged(resource: null, id: null, action: 'login');
        $dashboard = $this->urlFor('app.dashboard');
        $redirect_uri = $this->request->getHeaderLine('Referer');
        if (str_contains($redirect_uri, '/login')) $redirect_uri = $this->httpHost() . $dashboard;
        return $this->respondWithData(['message' => $result['msg'], 'redirect' => $redirect_uri]);
      } else {
        return $this->respondWithError($result['err']);
      }
    } else {
      if (!$this->admin->count()) return $this->response->withHeader('Location', $this->httpHost() . $this->urlFor('app.init'))->withStatus(302);
      $dashboard = $this->urlFor('app.dashboard');
      if ($this->getLoginId()) return $this->response->withHeader('Location', $this->httpHost() . $dashboard)->withStatus(301);
      $userAgent = $this->request->getHeaderLine('User-Agent');
      $isWechat = str_contains(strtolower($userAgent), 'micromessenger');
      if ($isWechat) {
        $data['redirect_uri'] = $this->urlFor('app.qrLogin', [], ['state' => 'weixin']);
      } else {
        session_regenerate_id(true);// 使用新生成的会话 ID 更新现有会话 ID

        $scene = bin2hex(random_bytes(16));
        $this->cache->set($scene, session_id(), 60);
        $baseUrl = $this->urlFor('app.qrLogin', [], ['tk' => $scene]);
        $data['loginQr'] = $this->writer->writeString($this->httpHost() . $baseUrl);
      }
      $data['systemName'] = getenv('APP_NAME');

      return $this->respondView('pages/home/login.twig', $data);
    }
  }

  /**
   * 系统初始化
   * @throws Exception
   */
  #[Route(path: '/initialization', methods: ['GET', 'POST'], description: '系统初始化', name: 'app.init')]
  public function initialization(Request $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;

    if ($this->isPost()) {
      $data = $this->getFormData();
      $initUser = $this->admin->initialization($data['account'], $data['password']);
      if ($initUser) {
        return $this->respondWxMsg('warn', '重复操作', '系统已初始化。');
      }
      return $this->response->withStatus(302)->withHeader('Location', $this->urlFor('app.login'));
    }


    // 检查是否添加了初始化用户与客户端
    $account = bin2hex(random_bytes(4));
    // 生成密码
    $sets = [
      'abcdefghijklmnopqrstuvwxyz',
      'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
      '0123456789',
      '!@#$%^&*'
    ];

    $allChars = implode('', $sets);
    $passwordArray = [];

    // 确保每个类别至少出现一次
    foreach ($sets as $set) {
      $passwordArray[] = $set[random_int(0, strlen($set) - 1)];
    }

    // 随机决定最终长度（8-16位）并填充剩余位
    $targetLength = random_int(8, 16);
    while (count($passwordArray) < $targetLength) {
      $passwordArray[] = $allChars[random_int(0, strlen($allChars) - 1)];
    }

    // 打乱字符串顺序（防止必填项总是出现在开头）
    shuffle($passwordArray);
    if (!ctype_alpha($passwordArray[0])) {
      foreach ($passwordArray as $key => $char) {
        if (ctype_alpha($char)) {
          // 交换位置
          $temp = $passwordArray[0];
          $passwordArray[0] = $char;
          $passwordArray[$key] = $temp;
          break;
        }
      }
    }

    $password = implode('', $passwordArray);
    if ($this->admin->count() > 0) {
      return $this->response->withStatus(302)->withHeader('Location', $this->urlFor('app.login'));
    }
    $loginUrl = $this->httpHost() . $this->urlFor('app.login');
    $html = <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
  <meta charset="utf-8">
  <title>系统初始化</title>
  <meta name="viewport" content="width=device-width,initial-scale=1,user-scalable=0">
  <style>
    body {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      background-color: #f8f8f8;
    }
    .container {
      width: 480px;
      text-align: center;
      background: white;
      padding: 30px;
      border-radius: 8px;
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
    .h2 {
      font-size: 17px;
      margin-bottom: 16px;
      color: #333;
    }
    .body p{
      text-align: left;
    }
    .body span{
      color: orange;
    }
    .body input {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      box-sizing: border-box;
    }
    .confirm {
      display:block;
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
    .confirm:hover {
      background-color: #1e40af;
    }
  </style>
</head>
<body>
  <div class="container">
    <h2>系统初始化</h2>
    <p style="color: red">注意！！初始化信息只显示一次，请妥善保存！否则无法登录系统。</p>
    <div class="body">
      <form id="myForm" action="" method="POST">
        <p>
          <input type="text" name="account" value="$account" placeholder="初始化用户账号" required autocomplete="off">
          <br><span>初始化用户账号，可修改</span>
        </p>
        <p>
          <input type="text" name="password" value="$password" placeholder="初始化用户密码" required
           pattern="^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[!@#$%^&*])[A-Za-z\d!@#$%^&*]{8,16}$" autocomplete="off">
          <br><span>密码必须包含至少一个大写字母，一个小写字母，一个数字和一个特殊字符!@#$%^&*，长度8-16位</span>
        </p>
        <button class="confirm" type="submit">确认信息已保存，登录系统</button>
      </form>
    </div>
  </div>
  <script>
  document.getElementById('myForm').addEventListener('submit', function (e) {
    e.preventDefault();
  
    const form = this;
    const formData = new FormData(form);
  
    let txt = `登录地址：$loginUrl`;
    txt+=`\n用户帐号：`+formData.get('account');
    txt+=`\n用户密码：`+formData.get('password');
    
    const blob = new Blob([txt], { type: "text/plain;charset=utf-8" });
    const url = URL.createObjectURL(blob);
  
    const a = document.createElement("a");
    a.href = url;
    a.download = "init-info.txt";
    a.click();
  
    URL.revokeObjectURL(url);
    setTimeout(() => {form.submit();}, 300);
  });
  </script>
</body>
</html>
HTML;
    $this->response->getBody()->write($html);
    return $this->response;
  }
}
