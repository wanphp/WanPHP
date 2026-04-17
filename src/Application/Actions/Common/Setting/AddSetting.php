<?php

namespace App\Application\Actions\Common\Setting;

use App\Application\Middleware\PermissionMiddleware;
use App\Service\Common\SettingService;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class AddSetting extends Action
{
  public function __construct(private readonly SettingService $settingService)
  {
  }

  /**
   * @inheritDoc
   */
  #[Route(
    path: '/setting/add',
    methods: ['GET', 'POST'],
    description: '添加自定义配置',
    name: 'app.setting.add',
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    if ($this->request->getMethod() === 'POST') {
      $data = $this->getFormData();
      $id = $this->settingService->save($data);
      return $this->respondWithData(['id' => $id], 201);
    } else {
      $data = [
        'title' => '自定义配置',
        'action' => $this->urlFor('app.setting.add'),
        'modalName' => 'app.setting.modal',
      ];

      return $this->respondView('pages/setting/setting-modal.twig', $data);
    }
  }
}