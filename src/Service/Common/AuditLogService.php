<?php

namespace App\Service\Common;

use App\Entities\Common\AuditLogEntity;
use Exception;
use WanPHP\Core\Database\EntityMetadata;
use WanPHP\Core\Factory\EntityMetadataFactory;
use WanPHP\Core\Repositories\Repository;
use WanPHP\Core\Service\Service;

class AuditLogService extends Service
{
  /**
   * @inheritDoc
   */
  protected function repo(): Repository
  {
    return $this->em->getRepository(AuditLogEntity::class);
  }

  /**
   * @inheritDoc
   */
  protected function meta(): EntityMetadata
  {
    return EntityMetadataFactory::from(AuditLogEntity::class);
  }

  /**
   * @throws Exception
   */
  public function insertLog(array $data): int
  {
    return $this->save($data);
  }
}