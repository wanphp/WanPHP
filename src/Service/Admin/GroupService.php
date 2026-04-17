<?php

namespace App\Service\Admin;

use App\Entities\Admin\AdminGroupEntity;
use Exception;
use WanPHP\Core\Database\EntityMetadata;
use WanPHP\Core\Factory\EntityMetadataFactory;
use WanPHP\Core\Repositories\Repository;
use WanPHP\Core\Service\Service;

class GroupService extends Service
{
  protected function repo(): Repository
  {
    return $this->em->getRepository(AdminGroupEntity::class);
  }

  protected function meta(): EntityMetadata
  {
    return EntityMetadataFactory::from(AdminGroupEntity::class);
  }

  /**
   * @throws Exception
   */
  public function checkGroup(string $name, int $id = 0): bool
  {
    $where = ['name' => $name];
    if ($id > 0) $where['id[!]'] = $id;
    return !empty($this->repo()->get('id', $where));
  }

}