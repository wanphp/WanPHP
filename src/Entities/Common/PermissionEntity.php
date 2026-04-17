<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/8/26
 * Time: 15:21
 */

namespace App\Entities\Common;

use Doctrine\DBAL\Types\Types;
use OpenApi\Attributes as OA;
use WanPHP\Core\Attribute\Column;
use WanPHP\Core\Attribute\DataTable;
use WanPHP\Core\Traits\EntityArrayTrait;

#[DataTable(name: 'sys_router', required: ["name", "path"])]
#[OA\Schema(title: "系统路由", description: "系统路由权限", required: ["name", "path"])]
class PermissionEntity
{
  use EntityArrayTrait;

  #[Column(type: Types::SMALLINT, autoIncrement: true, primary: true)]
  #[OA\Property(description: "主键ID")]
  private ?int $id;
  #[Column(type: Types::SMALLINT, index: true)]
  #[OA\Property(description: "所在导航菜单")]
  private int $navId;
  #[Column(type: Types::STRING, length: 50,unique: true)]
  #[OA\Property(description: "路由名称", type: "string")]
  private string $name;
  #[Column(type: Types::STRING, length: 20, index: true)]
  #[OA\Property(description: "显示名称", type: "string")]
  private string $description;
  #[Column(type: Types::JSON)]
  #[OA\Property(description: "请求方法", type: "array", items: new OA\Items(description: 'POST', type: "string"))]
  private array $methods;
  #[Column(type: Types::STRING, length: 80, unique: true)]
  #[OA\Property(description: "路由路径", type: "string")]
  private string $path;
  #[Column(type: Types::STRING, length: 80)]
  #[OA\Property(description: "回调", type: "string")]
  private string $callable;
  #[Column(type: Types::BOOLEAN)]
  #[OA\Property(description: "是否可以配置到菜单", type: "boolean")]
  private bool $isNav;
  #[Column(type: Types::STRING, length: 3, default: 0)]
  #[OA\Property(description: "排序", type: "integer")]
  private int $sortOrder = 0;

  /**
   * @return int|null
   */
  public function getId(): ?int
  {
    return $this->id;
  }

  /**
   * @param int|null $id
   * @return PermissionEntity
   */
  public function setId(?int $id): self
  {
    $this->id = $id;
    return $this;
  }

  /**
   * @return int
   */
  public function getNavId(): int
  {
    return $this->navId;
  }

  /**
   * @param int $navId
   * @return PermissionEntity
   */
  public function setNavId(int $navId): self
  {
    $this->navId = $navId;
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
   * @return PermissionEntity
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
   * @return PermissionEntity
   */
  public function setDescription(string $description): self
  {
    $this->description = $description;
    return $this;
  }

  /**
   * @return array
   */
  public function getMethods(): array
  {
    return $this->methods;
  }

  /**
   * @param array $methods
   * @return PermissionEntity
   */
  public function setMethods(array $methods): self
  {
    $this->methods = $methods;
    return $this;
  }

  /**
   * @return string
   */
  public function getPath(): string
  {
    return $this->path;
  }

  /**
   * @param string $path
   * @return PermissionEntity
   */
  public function setPath(string $path): self
  {
    $this->path = $path;
    return $this;
  }

  /**
   * @return string
   */
  public function getCallable(): string
  {
    return $this->callable;
  }

  /**
   * @param string $callable
   * @return PermissionEntity
   */
  public function setCallable(string $callable): self
  {
    $this->callable = $callable;
    return $this;
  }

  /**
   * @return bool
   */
  public function isNav(): bool
  {
    return $this->isNav;
  }

  /**
   * @param bool $isNav
   * @return PermissionEntity
   */
  public function setIsNav(bool $isNav): self
  {
    $this->isNav = $isNav;
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
   * @return PermissionEntity
   */
  public function setSortOrder(int $sortOrder): self
  {
    $this->sortOrder = $sortOrder;
    return $this;
  }
}
