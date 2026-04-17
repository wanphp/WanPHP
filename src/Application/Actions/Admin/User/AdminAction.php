<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/9/9
 * Time: 15:06
 */

namespace App\Application\Actions\Admin\User;


use App\Application\Middleware\PermissionMiddleware;
use App\Service\Admin\AdminService;
use Psr\Http\Message\ResponseInterface as Response;
use WanPHP\Core\Action;
use WanPHP\Core\Attribute\Route;
use WanPHP\Core\Service\UserService;

class AdminAction extends Action
{

  /**
   * @param AdminService $admin
   * @param UserService $user
   */
  public function __construct(
    private readonly AdminService $admin,
    private readonly UserService  $user)
  {
  }

  /**
   * @inheritDoc
   */
  #[Route(
    path: '/admin/admin-list[/{id:[0-9]+}]',
    methods: ['GET'],
    description: '管理员管理',
    name: 'app.admin.list',
    isNav: true,
    middleware: [PermissionMiddleware::class])
  ]
  protected function action(): Response
  {
    if ($this->isAjax()) {
      $params = $this->request->getQueryParams();
      $where = ['id[!]' => $this->getLoginId()];
      // 查看选择角色
      if (isset($params['role_id']) && $params['role_id'] > 0) {
        $where['role_id'] = intval($params['role_id']);
      } else if ($this->getLoginUserRoleId() > 0) {
        $where['role_id'] = $this->getLoginUserRoleId();
      }
      if (isset($params['groupId']) && $params['groupId'] > 0) $where['groupId'] = intval($params['groupId']);
      if ($_SESSION['role_id'] != -1) $where['parentId'] = $this->getLoginUserGroupId();//只显示自己添加的管理员

      $recordsTotal = $this->admin->count($where);
      if (!empty($params['search']['value'])) {
        $keyword = trim($params['search']['value']);
        $keyword = addcslashes($keyword, '*%_');
        $where['OR'] = [
          'account[~]' => $keyword,
          'name[~]' => $keyword,
          'tel[~]' => $keyword
        ];
      }

      $where['ORDER'] = ['status' => 'DESC', 'lastLoginTime' => 'DESC'];
      $recordsFiltered = $this->admin->count($where);
      $limit = $this->getLimit();
      if ($limit) $where['LIMIT'] = $limit;

      $admins = $this->admin->select('id,openid,role_id,groupId,name,tel,account,status,lastLoginTime,lastLoginIp,lastEditPwd', $where);
      $openid = array_unique(array_column($admins, 'openid'));
      // 绑定微信
      if (!empty($openid)) $users = $this->user->getUserInfo($openid);
      foreach ($admins as &$admin) {
        if (!empty($users[$admin['openid']])) $admin['user'] = $users[$admin['openid']];
        $admin['actions'] = [
          'edit' => [
            'name' => '修改',
            'path' => $this->urlFor('app.admin.edit', ['id' => $admin['id']]),
            'modal' => ['name' => 'app.admin.modal', 'size' => 'lg'],
          ],
          'delete' => [
            'name' => '删除',
            'path' => $this->urlFor('app.admin.delete', ['id' => $admin['id']]),
            'message' => '是否确认要删除此用户'
          ]
        ];
      }
      unset($admin);
      $data = [
        "draw" => $params['draw'],
        "recordsTotal" => $recordsTotal,
        "recordsFiltered" => $recordsFiltered,
        'data' => $admins
      ];

      return $this->respondWithData($data);
    } else {
      $data = [
        'title' => '管理员管理',
        'roles' => $this->admin->adminRole($this->getLoginUserRoleId()),
        'group' => $this->admin->adminGroup(),
      ];

      return $this->respondView('pages/admin/index.twig', $data);
    }
  }
}
