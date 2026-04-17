<?php

namespace App\Application\Actions\Home;

use App\Application\Middleware\PermissionMiddleware;
use App\Service\Department\IntegralService;
use App\Service\Department\StaffService;
use App\Service\News\ArticleService;
use App\Service\News\WxArticleService;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;

class DashboardAction extends Action
{
  public function __construct(
    private readonly ArticleService   $articleService,
    private readonly WxArticleService $wxArticleService,
    private readonly StaffService     $staffService,
    private readonly IntegralService  $integralService
  )
  {
  }

  #[Route(path: '/admin/dashboard', methods: ['GET'], description: '仪表盘', name: 'app.dashboard', middleware: [PermissionMiddleware::class])]
  protected function action(): Response
  {

    $start_timestamp = strtotime('first day of this month midnight');
    $end_timestamp = time();

    $data = [
      'newsCount' => $this->articleService->count(['repeat' => false, 'sent_time[<>]' => [$start_timestamp, $end_timestamp]]),
      'wxNewsCount' => $this->wxArticleService->count(['sent_time[<>]' => [$start_timestamp, $end_timestamp]]),
      'staffCount' => $this->staffService->count(),
      'integralCount' => $this->integralService->sumIntegral(date('Y-m')),
    ];
    return $this->respondView('pages/home/dashboard.twig', $data);
  }
}
