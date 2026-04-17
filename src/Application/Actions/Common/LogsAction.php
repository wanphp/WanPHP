<?php

namespace App\Application\Actions\Common;

use App\Application\Middleware\PermissionMiddleware;
use App\Service\Admin\AdminService;
use App\Service\Common\AuditLogService;
use DeviceDetector\DeviceDetector;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Service\UserService;

class LogsAction extends Action
{

  public function __construct(
    private readonly AuditLogService $logs,
    private readonly AdminService    $admin,
    private readonly UserService     $user
  )
  {
  }

  #[Route(path: '/audit/logs', methods: ['GET'], description: '系统日志审计', name: 'app.audit.logs', isNav: true, middleware: [PermissionMiddleware::class])]
  protected function action(): Response
  {
    if ($this->isAjax()) {
      $params = $this->request->getQueryParams();
      $where = [];
      if ($this->getLoginUserRoleId() > 0) $where['actor_id'] = $this->getLoginId();

      $recordsTotal = $this->logs->count($where);
      if (!empty($params['search']['value'])) {
        $keyword = trim($params['search']['value']);
        $where['action_desc[~]'] = addcslashes($keyword, '*%_');
      } else {
        if (isset($params['date'])) $where['event_time[<>]'] = [strtotime($params['date']), strtotime("+1 day", strtotime($params['date']))];
        else $where['event_time[>]'] = strtotime("-6 day");
      }

      $order = $this->getOrder();
      if ($order) $where['ORDER'] = $order;
      $recordsFiltered = $this->logs->count($where);
      $limit = $this->getLimit();
      if ($limit) $where['LIMIT'] = $limit;

      $logs = $this->logs->select('id,actor_type,client_id,actor_id,action,action_desc,event_time', $where);
      $admin_id = [];
      $openid = [];
      foreach ($logs as $log) {
        if ($log['actor_type'] == 'admin') $admin_id[$log['actor_id']] = true;
        elseif ($log['actor_type'] == 'user' && !empty($log['actor_id'])) $openid[$log['actor_id']] = true;
      }
      if (!empty($admin_id)) {
        $admins = array_column($this->admin->select('id,name', ['id' => array_keys($admin_id)]), 'name', 'id');
      }
      if (!empty($openid)) {
        $users = $this->user->getUserInfo(array_keys($openid));
      }
      foreach ($logs as &$log) {
        if ($log['actor_type'] == 'user' && !empty($log['actor_id'])) {
          $log['actor_type'] = '客户端：' . $log['client_id'];
          if (!empty($users[$log['actor_id']])) $log['user'] = $users[$log['actor_id']];
        } elseif ($log['actor_type'] == 'admin' && !empty($admins[$log['actor_id']])) {
          $log['actor_type'] = '后台用户';
          $log['user'] = $admins[$log['actor_id']];
        } else {
          if ($log['client_id']) $log['actor_type'] = '客户端：' . $log['client_id'];
          $log['user'] = '系统';
        }
        $log['detail'] = $this->urlFor('app.audit.logs.detail', ['id' => $log['id']]);
      }

      return $this->respondWithData([
        "draw" => $params['draw'],
        "recordsTotal" => $recordsTotal,
        "recordsFiltered" => $recordsFiltered,
        'admins' => $admins ?? [],
        'data' => $logs
      ]);
    } else {
      $data = [
        'title' => '系统日志审计(默认显示7天内)'
      ];

      return $this->respondView('pages/logs/index.twig', $data);
    }
  }

  /**
   * @throws Exception
   */
  #[Route(
    path: '/audit/logs/detail/{id:[0-9]+}',
    methods: ['GET'],
    description: '日志详情',
    name: 'app.audit.logs.detail',
    middleware: [PermissionMiddleware::class])
  ]
  public function syncNewsHits(ServerRequestInterface $request, Response $response, array $args): Response
  {
    $this->request = $request;
    $this->response = $response;
    $this->args = $args;

    $id = $this->resolveArg('id', 0);
    if ($id > 0) {
      $log = $this->logs->load($id);
      $data = [
        'title' => '<span class="text-secondary font-monospace fs-6">ID: ' . $log['request_id'] . '</span>',
        'modalName' => 'app.audit.logs.detail'
      ];
      if ($log['actor_type'] == 'user' && !empty($log['actor_id'])) {
        $log['actor_type'] = '客户端：' . $log['client_id'];
        $user = $this->user->getUser($log['actor_id']);
        $log['user'] = $user['name'] ?? $user['nickname'] ?? $user['tel'] ?? $user['openid'];
      } elseif ($log['actor_type'] == 'admin' && !empty($log['actor_id'])) {
        $log['actor_type'] = '后台用户';
        $log['user'] = $this->admin->getColumn('name', ['id' => $log['actor_id']]);
      } else {
        if ($log['client_id']) $log['actor_type'] = '客户端：' . $log['client_id'];
        $log['user'] = '系统';
      }

      $dd = new DeviceDetector($log['user_agent']);
      $dd->parse();
      $os = $dd->getOs('name') . $dd->getOs('version');
      $browse = $dd->getClient('name') . $dd->getClient('version');
      $log['device'] = "{$dd->getDeviceName()} {$dd->getBrandName()} {$dd->getModel()} $os $browse";

      $log['context'] = json_decode($log['context'], true);
      $data['log'] = $log;
      return $this->respondView('pages/logs/detail.twig', $data);
    }
    return $this->respondWithError('ID错误');
  }
}
