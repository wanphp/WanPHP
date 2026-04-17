<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/8/26
 * Time: 17:11
 */

namespace App\Entities\Admin;


use Doctrine\DBAL\Types\Types;
use OpenApi\Attributes as OA;
use WanPHP\Core\Attribute\Column;
use WanPHP\Core\Attribute\DataTable;
use WanPHP\Core\Traits\EntityArrayTrait;

#[DataTable(name: 'admin_role', required: ["name"])]
#[OA\Schema(title: "管理员角色", description: "系统管理角色", required: ["name"])]
class RoleEntity
{
  use EntityArrayTrait;

  #[Column(type: Types::SMALLINT, autoIncrement: true, primary: true)]
  #[OA\Property(description: "管理角色ID")]
  private ?int $id;
  #[Column(type: Types::STRING, length: 20, unique: true)]
  #[OA\Property(description: "角色名称", type: "string")]
  private string $name;
  #[Column(type: Types::JSON)]
  #[OA\Property(description: "未授权范围", type: "array", items: new OA\Items(description: '权限', type: "string"))]
  private array $scopes;

  /**
   * @return int|null
   */
  public function getId(): ?int
  {
    return $this->id;
  }

  /**
   * @param int|null $id
   * @return RoleEntity
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
   * @return RoleEntity
   */
  public function setName(string $name): self
  {
    $this->name = $name;
    return $this;
  }

  /**
   * @return array
   */
  public function getScopes(): array
  {
    return $this->scopes;
  }

  /**
   * @param array $scopes
   * @return RoleEntity
   */
  public function setScopes(array $scopes): self
  {
    $this->scopes = $scopes;
    return $this;
  }

}
