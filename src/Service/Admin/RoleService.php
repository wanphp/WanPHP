<?php

namespace App\Service\Admin;

use App\Entities\Admin\RoleEntity;
use Exception;
use WanPHP\Core\Database\EntityMetadata;
use WanPHP\Core\Factory\EntityMetadataFactory;
use WanPHP\Core\Repositories\Repository;
use WanPHP\Core\Service\Service;

class RoleService extends Service
{
  /**
   * @inheritDoc
   */
  protected function repo(): Repository
  {
    return $this->em->getRepository(RoleEntity::class);
  }

  /**
   * @inheritDoc
   */
  protected function meta(): EntityMetadata
  {
    return EntityMetadataFactory::from(RoleEntity::class);
  }

  /**
   * @throws Exception
   */
  public function checkRole(string $name, int $id = 0): bool
  {
    $where = ['name' => $name];
    if ($id > 0) $where['id[!]'] = $id;
    return !empty($this->repo()->get('id', $where));
  }

  /**
   * @throws Exception
   */
  public function getRole(int $id): array
  {
    return $this->repo()->get('id,name,scopes[JSON]', ['id' => $id]);
  }
}