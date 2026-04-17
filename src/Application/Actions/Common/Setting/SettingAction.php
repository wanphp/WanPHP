<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/12/11
 * Time: 10:23
 */

namespace App\Application\Actions\Common\Setting;


use App\Application\Middleware\PermissionMiddleware;
use App\Service\Common\SettingService;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class SettingAction extends Action
{

  public function __construct(private readonly SettingService $setting)
  {
  }

  #[Route(
    path: '/admin/setting',
    methods: ['GET'],
    description: '自定义系统配置',
    name: 'app.setting',
    isNav: true,
    middleware: [PermissionMiddleware::class]
  )]
  protected function action(): Response
  {
    $data = [
      'title' => '自定义系统配置',
      'settings' => $this->setting->getAll()
    ];

    return $this->respondView('pages/setting/index.twig', $data);
  }
}
