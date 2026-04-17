<?php

namespace App\Application\Actions\Common\Setting;

use App\Application\Middleware\PermissionMiddleware;
use App\Service\Common\SettingService;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Service\UploaderService;

class DeleteSetting extends Action
{
  public function __construct(
    private readonly SettingService  $settingService,
    private readonly UploaderService $uploader
  )
  {
  }

  #[Route(
    path: '/setting/delete/{id:[0-9]+}',
    methods: ['DELETE'],
    description: '删除自定义配置',
    name: 'app.setting.delete',
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    $id = (int)$this->resolveArg('id', 0);
    if ($id > 0) {
      $path = $this->settingService->getConfigById($id);
      $isImage = in_array(pathinfo($path, PATHINFO_EXTENSION), ['jpg', 'png', 'gif', 'webp']);
      $num = $this->settingService->delete($id);
      // 删除图片
      if ($isImage && $num > 0) $this->uploader->delFile($path);
      return $this->respondWithData(['message' => '删除成功！'], 204);
    } else {
      return $this->respondWithError('缺少ID');
    }
  }
}