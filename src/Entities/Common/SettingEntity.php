<?php
/**
 * Created by PhpStorm.
 * User: 火子 QQ：284503866.
 * Date: 2020/8/29
 * Time: 21:12
 */

namespace App\Entities\Common;


use Doctrine\DBAL\Types\Types;
use OpenApi\Attributes as OA;
use WanPHP\Core\Attribute\Column;
use WanPHP\Core\Attribute\DataTable;
use WanPHP\Core\Traits\EntityArrayTrait;

#[DataTable(name: 'sys_setting', required: ["name", "key", "value"])]
#[OA\Schema(title: "系统自定义配置", description: "系统自定义配置", required: ["name", "key", "value"])]
class SettingEntity
{
  use EntityArrayTrait;

  #[Column(type: Types::SMALLINT, autoIncrement: true, primary: true)]
  #[OA\Property(description: "主键ID")]
  private int $id;
  #[Column(type: Types::STRING, length: 20, index: true)]
  #[OA\Property(description: "配置项名称")]
  private string $name;
  #[Column(type: Types::STRING, length: 30, unique: true)]
  #[OA\Property(description: "配置项键")]
  private string $key;
  #[Column(type: Types::STRING, length: 300)]
  #[OA\Property(description: "配置项值")]
  private string $value;

  /**
   * @return int
   */
  public function getId(): int
  {
    return $this->id;
  }

  /**
   * @param int $id
   * @return SettingEntity
   */
  public function setId(int $id): self
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
   * @return SettingEntity
   */
  public function setName(string $name): self
  {
    $this->name = $name;
    return $this;
  }

  /**
   * @return string
   */
  public function getKey(): string
  {
    return $this->key;
  }

  /**
   * @param string $key
   * @return SettingEntity
   */
  public function setKey(string $key): self
  {
    $this->key = $key;
    return $this;
  }

  /**
   * @return string
   */
  public function getValue(): string
  {
    return $this->value;
  }

  /**
   * @param string $value
   * @return SettingEntity
   */
  public function setValue(string $value): self
  {
    $this->value = $value;
    return $this;
  }
}
