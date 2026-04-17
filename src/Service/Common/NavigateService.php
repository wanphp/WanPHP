<?php

namespace App\Service\Common;

use App\Entities\Common\NavigateEntity;
use Exception;
use WanPHP\Core\Database\EntityMetadata;
use WanPHP\Core\Factory\EntityMetadataFactory;
use WanPHP\Core\Repositories\Repository;
use WanPHP\Core\Service\Service;

class NavigateService extends Service
{
  /**
   * @throws Exception
   */
  public function checkNav(string $name, int $id = 0): bool
  {
    $where = ['name' => $name];
    if ($id > 0) $where['id[!]'] = $id;
    return !empty($this->repo()->get('id', $where));
  }

  /**
   * @inheritDoc
   */
  protected function repo(): Repository
  {
    return $this->em->getRepository(NavigateEntity::class);
  }

  /**
   * @inheritDoc
   */
  protected function meta(): EntityMetadata
  {
    return EntityMetadataFactory::from(NavigateEntity::class);
  }
}