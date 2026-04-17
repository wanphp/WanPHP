<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/8/26
 * Time: 16:17
 */

namespace App\Entities\Admin;

use Doctrine\DBAL\Types\Types;
use OpenApi\Attributes as OA;
use WanPHP\Core\Attribute\Column;
use WanPHP\Core\Attribute\DataTable;
use WanPHP\Core\Traits\EntityArrayTrait;

#[DataTable(name: 'admin', required: ["account", "password"])]
#[OA\Schema(title: "系统管理员", description: "系统管理员", required: ["account", "password"])]
class AdminEntity
{
  use EntityArrayTrait;

  #[Column(type: Types::SMALLINT, autoIncrement: true, primary: true)]
  #[OA\Property(description: "管理员ID")]
  private ?int $id;
  #[Column(type: Types::STRING, length: 50, unique: true)]
  #[OA\Property(description: "登录帐号", type: "string")]
  private string $account;
  #[Column(type: Types::STRING, length: 60)]
  #[OA\Property(description: "登录密码", type: "string")]
  private string $password;
  #[Column(type: Types::SMALLINT, default: 0, index: true)]
  #[OA\Property(description: "添加管理员的管理员id")]
  private int $parentId;
  #[Column(type: Types::STRING, length: 28, nullable: true, unique: true)]
  #[OA\Property(description: "绑定公众号/小程序openid", type: "string")]
  private string $openid;
  #[Column(type: Types::STRING, length: 16, index: true)]
  #[OA\Property(description: "用户姓名", type: "string")]
  protected string $name;
  #[Column(type: Types::STRING, length: 11, nullable: true, unique: true)]
  #[OA\Property(description: "用户联系电话", type: "string")]
  protected string $tel;
  #[Column(type: Types::SMALLINT, default: 0, index: true)]
  #[OA\Property(description: "角色ID")]
  private int $role_id;
  #[Column(type: Types::SMALLINT, default: 0, index: true)]
  #[OA\Property(description: "分组ID")]
  private int $groupId;
  #[Column(type: Types::STRING, length: 10)]
  #[OA\Property(description: "最后登录时间", type: "string")]
  private int $lastLoginTime;
  #[Column(type: Types::STRING, length: 50)]
  #[OA\Property(description: "最后登录IP", type: "string")]
  private string $lastLoginIp;
  #[Column(type: Types::STRING, length: 10)]
  #[OA\Property(description: "最后修改密码", type: "string")]
  private string $lastEditPwd;
  #[Column(type: Types::BOOLEAN)]
  #[OA\Property(description: "帐号状态", type: "int")]
  private int $status;
  #[Column(type: Types::STRING, length: 10, index: true)]
  #[OA\Property(description: "创建时间", type: "integer")]
  private int $createdAt;

  /**
   * @return int|null
   */
  public function getId(): ?int
  {
    return $this->id;
  }

  /**
   * @param int|null $id
   * @return AdminEntity
   */
  public function setId(?int $id): self
  {
    $this->id = $id;
    return $this;
  }

  /**
   * @return string
   */
  public function getAccount(): string
  {
    return $this->account;
  }

  /**
   * @param string $account
   * @return AdminEntity
   */
  public function setAccount(string $account): self
  {
    $this->account = $account;
    return $this;
  }

  /**
   * @return string
   */
  public function getPassword(): string
  {
    return $this->password;
  }

  /**
   * @param string $password
   * @return AdminEntity
   */
  public function setPassword(string $password): self
  {
    $this->password = password_hash($password, PASSWORD_BCRYPT);
    return $this;
  }

  /**
   * @return int
   */
  public function getParentId(): int
  {
    return $this->parentId;
  }

  /**
   * @param int $parentId
   * @return AdminEntity
   */
  public function setParentId(int $parentId): self
  {
    $this->parentId = $parentId;
    return $this;
  }

  /**
   * @return string
   */
  public function getOpenid(): string
  {
    return $this->openid;
  }

  /**
   * @param string $openid
   * @return AdminEntity
   */
  public function setOpenid(string $openid): self
  {
    $this->openid = $openid;
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
   * @return AdminEntity
   */
  public function setName(string $name): self
  {
    $this->name = $name;
    return $this;
  }

  /**
   * @return string
   */
  public function getTel(): string
  {
    return $this->tel;
  }

  /**
   * @param string $tel
   */
  public function setTel(string $tel): self
  {
    $this->tel = $tel;
    return $this;
  }

  /**
   * @return int
   */
  public function getRoleId(): int
  {
    return $this->role_id;
  }

  /**
   * @param int $role_id
   */
  public function setRoleId(int $role_id): self
  {
    $this->role_id = $role_id;
    return $this;
  }

  /**
   * @return int
   */
  public function getGroupId(): int
  {
    return $this->groupId;
  }

  /**
   * @param int $groupId
   * @return AdminEntity
   */
  public function setGroupId(int $groupId): self
  {
    $this->groupId = $groupId;
    return $this;
  }

  /**
   * @return int
   */
  public function getLastLoginTime(): int
  {
    return $this->lastLoginTime;
  }

  /**
   * @param int $lastLoginTime
   * @return AdminEntity
   */
  public function setLastLoginTime(int $lastLoginTime): self
  {
    $this->lastLoginTime = $lastLoginTime;
    return $this;
  }

  /**
   * @return string
   */
  public function getLastLoginIp(): string
  {
    return $this->lastLoginIp;
  }

  /**
   * @param string $lastLoginIp
   * @return AdminEntity
   */
  public function setLastLoginIp(string $lastLoginIp): self
  {
    $this->lastLoginIp = $lastLoginIp;
    return $this;
  }

  /**
   * @return string
   */
  public function getLastEditPwd(): string
  {
    return $this->lastEditPwd;
  }

  /**
   * @param string $lastEditPwd
   * @return AdminEntity
   */
  public function setLastEditPwd(string $lastEditPwd): self
  {
    $this->lastEditPwd = $lastEditPwd;
    return $this;
  }

  /**
   * @return int
   */
  public function getStatus(): int
  {
    return $this->status;
  }

  /**
   * @param int $status
   * @return AdminEntity
   */
  public function setStatus(int $status): self
  {
    $this->status = $status;
    return $this;
  }

  /**
   * @return string
   */
  public function getCreatedAt(): string
  {
    return $this->createdAt;
  }

  /**
   * @param string $createdAt
   * @return AdminEntity
   */
  public function setCreatedAt(string $createdAt): self
  {
    $this->createdAt = $createdAt;
    return $this;
  }

}
