<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/8/26
 * Time: 17:49
 */

namespace App\Entities\Common;


use Doctrine\DBAL\Types\Types;
use OpenApi\Attributes as OA;
use WanPHP\Core\Attribute\Column;
use WanPHP\Core\Attribute\DataTable;
use WanPHP\Core\Traits\EntityArrayTrait;

#[DataTable(name: 'sys_navigate', required: ["icon", "name"])]
#[OA\Schema(title: "系统导航", description: "系统导航菜单", required: ["icon", "name"])]
class NavigateEntity
{
  use EntityArrayTrait;

  #[Column(type: Types::SMALLINT, autoIncrement: true, primary: true)]
  #[OA\Property(description: "主键ID")]
  private ?int $id;
  #[Column(type: Types::STRING, length: 30)]
  #[OA\Property(description: "图标样式", type: "string")]
  private string $icon;
  #[Column(type: Types::STRING, length: 30,unique: true)]
  #[OA\Property(description: "导航名称", type: "string")]
  private string $name;
  #[Column(type: Types::STRING, length: 3)]
  #[OA\Property(description: "排序", type: "integer")]
  private int $sortOrder;

  /**
   * @return int|null
   */
  public function getId(): ?int
  {
    return $this->id;
  }

  /**
   * @param int|null $id
   * @return NavigateEntity
   */
  public function setId(?int $id): self
  {
    $this->id = $id;
    return $this;
  }

  /**
   * @return string
   */
  public function getIcon(): string
  {
    return $this->icon;
  }

  /**
   * @param string $icon
   * @return NavigateEntity
   */
  public function setIcon(string $icon): self
  {
    $this->icon = $icon;
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
   * @return NavigateEntity
   */
  public function setName(string $name): self
  {
    $this->name = $name;
    return $this;
  }

  /**
   * @return int
   */
  public function getSortOrder(): int
  {
    return $this->sortOrder;
  }

  /**
   * @param int $sortOrder
   * @return NavigateEntity
   */
  public function setSortOrder(int $sortOrder): self
  {
    $this->sortOrder = $sortOrder;
    return $this;
  }

}
