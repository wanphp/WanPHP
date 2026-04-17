<?php

namespace App\Entities\Admin;

use Doctrine\DBAL\Types\Types;
use OpenApi\Attributes as OA;
use WanPHP\Core\Attribute\Column;
use WanPHP\Core\Attribute\DataTable;
use WanPHP\Core\Traits\EntityArrayTrait;

#[DataTable(name: 'admin_group', required: ["name"])]
#[OA\Schema(title: "管理员分组", description: "系统管理员分组", required: ["name"])]
class AdminGroupEntity
{
  use EntityArrayTrait;

  #[Column(type: Types::SMALLINT, autoIncrement: true, primary: true)]
  #[OA\Property(description: "管理员分组ID")]
  private ?int $id;
  #[Column(type: Types::STRING, length: 50, unique: true)]
  #[OA\Property(description: "分组名称", type: "string")]
  private string $name;
  #[Column(type: Types::STRING, length: 300)]
  #[OA\Property(description: "分组说明", type: "string")]
  private string $description;
  #[Column(type: Types::SMALLINT, default: 0, index: true)]
  #[OA\Property(description: "显示排序")]
  private int $displayOrder;

  /**
   * @return int|null
   */
  public function getId(): ?int
  {
    return $this->id;
  }

  /**
   * @param int|null $id
   * @return AdminGroupEntity
   */
  public function setId(?int $id): self
  {
    $this->id = $id;
    return $this;
  }

  /**
   * @return string
   */
  public function getName(): string
  {
    return $this->name;
  }

  /**
   * @param string $name
   * @return AdminGroupEntity
   */
  public function setName(string $name): self
  {
    $this->name = $name;
    return $this;
  }

  /**
   * @return string
   */
  public function getDescription(): string
  {
    return $this->description;
  }

  /**
   * @param string $description
   * @return AdminGroupEntity
   */
  public function setDescription(string $description): self
  {
    $this->description = $description;
    return $this;
  }

  /**
   * @return int
   */
  public function getDisplayOrder(): int
  {
    return $this->displayOrder;
  }

  /**
   * @param int $displayOrder
   * @return AdminGroupEntity
   */
  public function setDisplayOrder(int $displayOrder): self
  {
    $this->displayOrder = $displayOrder;
    return $this;
  }

}
