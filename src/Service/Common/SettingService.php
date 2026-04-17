<?php

namespace App\Service\Common;

use App\Entities\Common\SettingEntity;
use Exception;
use WanPHP\Core\Database\EntityMetadata;
use WanPHP\Core\Factory\EntityMetadataFactory;
use WanPHP\Core\Repositories\Repository;
use WanPHP\Core\Service\Service;

class SettingService extends Service
{
  /**
   * @inheritDoc
   */
  protected function repo(): Repository
  {
    return $this->em->getRepository(SettingEntity::class);
  }

  /**
   * @inheritDoc
   */
  protected function meta(): EntityMetadata
  {
    return EntityMetadataFactory::from(SettingEntity::class);
  }

  /**
   * @throws Exception
   */
  public function getConfig(string $key): mixed
  {
    if (empty($key)) return '';
    return $this->repo()->get('value', ['key' => $key]);
  }

  /**
   * @throws Exception
   */
  public function getConfigById(int $id): mixed
  {
    if ($id < 1) return '';
    return $this->repo()->get('value', ['id' => $id]);
  }
}