<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/9/9
 * Time: 17:35
 */

namespace App\Service\Common;

use App\Entities\Admin\RoleEntity;
use App\Entities\Common\NavigateEntity;
use App\Entities\Common\PermissionEntity;
use Exception;
use Psr\SimpleCache\CacheInterface;
use WanPHP\Core\Database\EntityMetadata;
use WanPHP\Core\Database\EntityManager;
use WanPHP\Core\Factory\EntityMetadataFactory;
use WanPHP\Core\Repositories\Repository;
use WanPHP\Core\Service\Service;

class PersistenceService extends Service
{
  private Repository $roleRepository;
  private Repository $navigateRepository;
  private array $permission = [];//授权
  private array $restricted = [];//限制

  /**
   * @throws Exception
   */
  public function __construct(EntityManager $em, private readonly CacheInterface $cache)
  {
    parent::__construct($em);
    $this->roleRepository = $em->getRepository(RoleEntity::class);
    $this->navigateRepository = $em->getRepository(NavigateEntity::class);
  }

  /**
   * @inheritDoc
   */
  protected function repo(): Repository
  {
    return $this->em->getRepository(PermissionEntity::class);
  }

  /**
   * @inheritDoc
   */
  protected function meta(): EntityMetadata
  {
    return EntityMetadataFactory::from(PermissionEntity::class);
  }

  /**
   * @throws Exception
   */
  public function syncRoutes(array $permissions): void
  {
    $allRoute = $this->select('id,name,path');
    $nameMap = array_column($allRoute, 'id', 'name');
    $pathMap = array_column($allRoute, 'id', 'path');
    $syncIds = [];
    foreach ($permissions as $permission) {
      if (
        isset($nameMap[$permission['name']]) &&
        isset($pathMap[$permission['path']]) &&
        $nameMap[$permission['name']] !== $pathMap[$permission['path']]
      ) {
        throw new \LogicException(
          "路由冲突: {$permission['name']} <-> {$permission['path']}"
        );
      }

      if (isset($nameMap[$permission['name']])) {//更新path
        $this->repo()->update($permission, ['id' => $nameMap[$permission['name']]]);
        $syncIds[] = $nameMap[$permission['name']];
      } else if (isset($pathMap[$permission['path']])) {//更新name
        $this->repo()->update($permission, ['id' => $pathMap[$permission['path']]]);
        $syncIds[] = $pathMap[$permission['path']];
      } else {//新增
        $this->save($permission);
      }
    }
    // 清理旧路由
    $allExistingIds = array_column($allRoute, 'id');
    $delIds = array_diff($allExistingIds, $syncIds);

    if (count($delIds) > 0) {
      $this->repo()->delete(['id' => $delIds]);
    }
  }

  /**
   * @param int $role_id
   * @return void
   * @throws Exception
   */
  public function setPermission(int $role_id): void
  {
    $cacheKey = 'authority_' . $role_id;
    $authority = $this->cache->get($cacheKey);
    if (!$authority) {
      $routers = $this->select('id,navId,name,description,callable', ['isNav' => true, 'ORDER' => ['sortOrder' => 'ASC']]);
      if ($routers) {
        //角色限制权限
        if ($role_id) {
          if ($role_id < 0) {
            //超级管理员不限制权限
            foreach ($routers as $action) {
              $this->permission[$action['navId']][] = ['description' => $action['description'], 'name' => $action['name']];
            }
            $this->restricted = [];
          } else {
            $restricted = $this->roleRepository->get('restricted[JSON]', ['id' => $role_id]);
            if ($restricted) {
              foreach ($routers as $action) {
                if (in_array($action['id'], $restricted)) {
                  $this->restricted[] = $action['callable'];
                } else {
                  $this->permission[$action['navId']][] = ['description' => $action['description'], 'name' => $action['name']];
                }
              }
            } else {//未找到角色
              $this->permission = [];
              $this->restricted = array_column($routers, 'callable');
            }
          }
        } else {//未配置角色,限制所有权限
          $this->permission = [];
          $this->restricted = array_column($routers, 'callable');
        }

        $this->cache->set($cacheKey, ['permission' => $this->permission, 'restricted' => $this->restricted]);
      }
    } else {
      $this->permission = $authority['permission'];
      $this->restricted = $authority['restricted'];
    }
  }

  /**
   * @throws Exception
   */
  public function getSidebar(): array
  {
    $sidebar = [];
    $navigate = $this->cache->get('navigate');
    if (!$navigate) {
      $navigate = $this->navigateRepository->select('id,icon,name', ['ORDER' => ['sortOrder' => 'ASC']]);
      $this->cache->set('navigate', $navigate);
    }

    if ($navigate) foreach ($navigate as $item) {
      if (isset($this->permission[$item['id']])) $sidebar[$item['id']] = ['icon' => $item['icon'], 'name' => $item['name'], 'children' => $this->permission[$item['id']]];
    }
    return $sidebar;
  }

  public function hasRestricted($callable): bool
  {
    return in_array($callable, $this->restricted);
  }

  /**
   * @throws Exception
   */
  public function updateNavId(int $id, int $navId): int
  {
    $sortOrder = 0;
    if ($navId > 0) $sortOrder = $this->count(['navId' => $navId]);
    else $this->repo()->update(['sortOrder[-]' => 1], ['navId' => $navId, 'sortOrder[>]' => $this->repo()->get('sortOrder', ['id' => $id])], false);
    return $this->repo()->update(['navId' => $navId, 'sortOrder' => $sortOrder], ['id' => $id], false);
  }

  /**
   * @throws Exception
   */
  public function updateSortOrder(int $navId, int $id, int $newIndex, int $oldIndex): int
  {
    if ($newIndex > $oldIndex) {
      $num = $this->repo()->update(['sortOrder[-]' => 1], ['navId' => $navId, 'sortOrder[<>]' => [$oldIndex, $newIndex]], false);
    } else {
      $num = $this->repo()->update(['sortOrder[+]' => 1], ['navId' => $navId, 'sortOrder[<>]' => [$newIndex, $oldIndex]], false);
    }
    $num += $this->repo()->update(['sortOrder' => $newIndex], ['id' => $id], false);
    return $num;
  }
}
