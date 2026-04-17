<?php

namespace App\Service\Admin;

use App\Entities\Admin\AdminEntity;
use App\Entities\Admin\AdminGroupEntity;
use App\Entities\Admin\RoleEntity;
use App\Entities\Common\AuditLogEntity;
use Exception;
use WanPHP\Core\Database\EntityMetadata;
use WanPHP\Core\Factory\EntityMetadataFactory;
use WanPHP\Core\Repositories\Repository;
use WanPHP\Core\Service\Service;

class AdminService extends Service
{

  protected function repo(): Repository
  {
    return $this->em->getRepository(AdminEntity::class);
  }

  protected function meta(): EntityMetadata
  {
    return EntityMetadataFactory::from(AdminEntity::class);
  }

  /**
   * @throws Exception
   */
  public function initialization(string $account, string $password): bool
  {
    if (!$this->count()) {
      $this->em->getRepository(AuditLogEntity::class)->select();
      $admin = new AdminEntity()->setAccount($account)
        ->setPassword($password)
        ->setStatus(1)
        ->setRoleId(-1)
        ->setName('root')
        ->setGroupId(0);
      $this->insertEntityToArray($admin->toArray());
      return false;
    }
    return true;
  }

  /**
   * @throws Exception
   */
  public function createAdmin($data): AdminEntity
  {
    $admin = new AdminEntity()->setAccount($data['account'])
      ->setStatus($data['status'])
      ->setRoleId($data['roleId'])
      ->setGroupId($data['groupId']);
    if (!empty($data['name'])) $admin->setName($data['name']);
    if (!empty($data['tel'])) $admin->setTel($data['tel']);
    if (!empty($data['password'])) $admin->setPassword($data['password']);
    if (!empty($data['openid'])) $admin->setOpenid($data['openid']);
    return $admin;
  }

  /**
   * @throws Exception
   */
  public function checkAdmin(string $account, int $id = 0): bool
  {
    $where = ['account' => $account];
    if ($id > 0) $where['id[!]'] = $id;
    return !empty($this->repo()->get('id', $where));
  }

  /**
   * @throws Exception
   */
  public function adminRole($role_id): array
  {
    $roleRepository = $this->em->getRepository(RoleEntity::class);
    if ($role_id < 0) $role = $roleRepository->select('id,name');
    else $role = $roleRepository->select('id,name', ['id[>]' => $role_id]);
    if (empty($role)) return [];
    return array_column($role, 'name', 'id');
  }

  /**
   * @throws Exception
   */
  public function adminGroup(): array
  {
    $group = $this->em->getRepository(AdminGroupEntity::class)->select('id,name', ['ORDER' => ['displayOrder' => 'ASC']]);
    if (empty($group)) return [];
    return array_column($group, 'name', 'id');
  }

  /**
   * @throws Exception
   */
  public function clearGroup(int $groupId): bool
  {
    return $this->repo()->update(['groupId' => 0], ['groupId' => $groupId], false);
  }

  /**
   * @throws Exception
   */
  public function clearRole(int $roleId): bool
  {
    return $this->repo()->update(['role_id' => 0], ['role_id' => $roleId], false);
  }

  public function userLogin(string $account, string $password): array
  {
    $account = trim($account);
    $password = trim($password);

    // 用户使用密码登录
    try {
      $count = $this->count();
      if ($count == 0) {//没有添加过管理员
        $admin = new AdminEntity()->setAccount($account)->setPassword($password)->setRoleId(-1)->setStatus(1);
        $id = $this->repo()->insert($admin->toArray(), false);
        $_SESSION['login_id'] = $id;
        $_SESSION['role_id'] = -1;
        $_SESSION['groupId'] = 0;
        $_SESSION['user_openid'] = '';
        return ['id' => $id, 'msg' => '系统初始化并登录成功！'];
      } else {
        $user = $this->repo()->get('id,role_id,groupId,openid,password,status', ['OR' => ['tel' => $account, 'account' => $account]]);
      }
    } catch (Exception $e) {
      return ['err' => $e->getMessage()];
    }
    if ($user) {
      if (!password_verify($password, $user['password'])) return ['err' => '帐号密码不正确,请核实！'];
      if (!$user['status']) return ['err' => '帐号已被锁定,无法登录，请联系管理员！'];
      $_SESSION['login_id'] = $user['id'];
      $_SESSION['role_id'] = $user['role_id'];
      $_SESSION['groupId'] = $user['groupId'];
      $_SESSION['user_openid'] = $user['openid'];
      return ['id' => $user['id'], 'openid' => $user['openid'], 'msg' => '登录成功！'];
    } else {
      return ['err' => '帐号不存在,请核实！'];
    }
  }

}