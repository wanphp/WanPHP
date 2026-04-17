<?php

namespace App\Application\Actions\Common\Setting;

use App\Application\Middleware\PermissionMiddleware;
use App\Service\Common\SettingService;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class EditSetting extends Action
{
  public function __construct(private readonly SettingService $settingService)
  {
  }

  /**
   * @inheritDoc
   */
  #[Route(
    path: '/setting/edit/{id:[0-9]+}',
    methods: ['GET', 'POST'],
    description: '修改自定义配置',
    name: 'app.setting.edit',
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    $id = (int)$this->resolveArg('id', 0);
    if ($id > 0) {
      if ($this->isPost()) {
        $data = $this->getFormData();
        $num = $this->settingService->update($id, $data);
        return $this->respondWithData(['upNum' => $num]);
      } else {
        $data = [
          'title' => '修改自定义配置',
          'setting' => $this->settingService->load($id),
          'action' => $this->urlFor('app.setting.edit', ['id' => $id]),
          'modalName' => 'app.setting.modal',
        ];

        return $this->respondView('pages/setting/setting-modal.twig', $data);
      }
    } else {
      return $this->respondWithError('ID错误');
    }


  }
}