<?php

namespace App\Service\Common;

use App\Entities\Common\PermissionEntity;
use WanPHP\Core\Database\EntityMetadata;
use WanPHP\Core\Factory\EntityMetadataFactory;
use WanPHP\Core\Repositories\Repository;
use WanPHP\Core\Service\Service;

class RouterService extends Service
{
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
}