<?php

namespace App\Application\Actions\Admin\User;

use App\Application\Common\Message\Message;
use App\Application\Middleware\PermissionMiddleware;
use App\Entities\Admin\AdminEntity;
use App\Service\Admin\AdminService;
use BaconQrCode\Writer;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\SimpleCache\CacheInterface;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class EditPassword extends Action
{

  /**
   * @param Writer $writer
   * @param CacheInterface $cache
   * @param Message $message
   * @param AdminService $admin
   */
  public function __construct(
    private readonly Writer         $writer,
    private readonly CacheInterface $cache,
    private readonly Message        $message,
    private readonly AdminService   $admin
  )
  {
  }

  /**
   * @inheritDoc
   */
  #[Route(path: '/admin/edit/password', methods: ['GET', 'POST'], description: '管理员修改密码', name: 'app.admin.edit.password', middleware: [PermissionMiddleware::class])]
  protected function action(): Response
  {
    if ($this->isPost()) {
      $data = $this->getFormData();
      if (!empty($data['password'])) {
        $admin_id = $this->getLoginId();
        $admin = new AdminEntity()->setPassword($data['password'])->setLastEditPwd(time());
        $this->admin->updateEntityToArray($admin->toArray(false), ['id' => $admin_id]);
        $admin = $this->admin->getColumn('openid,account', ['id' => $admin_id]);
        if (!empty($admin['openid'])) {
          $scene = bin2hex(random_bytes(16));
          $this->cache->set($scene, session_id(), 60);
          $home = $this->urlFor('app.dashboard', [], ['tk' => $scene]);
          $this->message->editPassword([
            'account' => $admin['account'],
            'password' => $data['password'],
            'url' => $this->httpHost() . $home
          ])->send([$admin['openid']]);
        }
        return $this->respondWithData(['message' => '密码修改成功！']);
      } else {
        return $this->respondWithError('密码不能为空！！');
      }
    } else {
      $scene = bin2hex(random_bytes(16));
      $this->cache->set($scene, session_id(), 60);
      $bindQr = $this->urlFor('app.userBind', [], ['tk' => $scene]);
      $unBindQr = $this->urlFor('app.userUnBind', [], ['tk' => $scene]);
      $data = [
        'title' => '修改密码',
        'bindQr' => $this->writer->writeString($this->httpHost() . $bindQr),
        'unBindQr' => $this->writer->writeString($this->httpHost() . $unBindQr),
        'modalName' => 'app.admin.edit.password'
      ];
      return $this->respondView('pages/admin/edit-password-modal.twig', $data);
    }

  }
}
