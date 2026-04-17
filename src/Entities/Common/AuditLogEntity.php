<?php

namespace App\Entities\Common;

use Doctrine\DBAL\Types\Types;
use OpenApi\Attributes as OA;
use WanPHP\Core\Attribute\Column;
use WanPHP\Core\Attribute\DataTable;
use WanPHP\Core\Traits\EntityArrayTrait;

#[DataTable(name: 'audit_logs')]
#[OA\Schema(title: "审计日志", description: "系统审计日志")]
class AuditLogEntity
{
  use EntityArrayTrait;

  #[Column(type: Types::BIGINT, autoIncrement: true, primary: true)]
  #[OA\Property(description: "日志ID")]
  private ?int $id;

  /**
   * ───────────────
   * 时间 & 关联
   * ───────────────
   */
  #[Column(type: Types::STRING, length: 10, index: true)]
  #[OA\Property(description: "事件发生时间", type: "integer")]
  private int $event_time;

  #[Column(type: Types::STRING, length: 36, nullable: true, index: true)]
  #[OA\Property(description: "请求ID")]
  private ?string $request_id = null;

  /**
   * ───────────────
   * 行为主体（Who）
   * ───────────────
   */
  #[Column(type: Types::STRING, length: 64, nullable: true, index: true)]
  #[OA\Property(description: "行为主体 用户ID、管理员ID、null")]
  private ?string $actor_id = null;

  #[Column(type: Types::STRING, length: 64, nullable: true, index: true)]
  #[OA\Property(description: "OAuth 客户端ID")]
  private ?string $client_id = null;

  #[Column(type: Types::STRING, length: 16, index: true)]
  #[OA\Property(description: "行为主体类型", enum: ["admin", "user", "system"])]
  private string $actor_type;

  /**
   * ───────────────
   * 行为语义（What）
   * ───────────────
   */
  #[Column(type: Types::STRING, length: 64, index: true)]
  #[OA\Property(description: "行为标识")]
  private string $action;

  #[Column(type: Types::STRING, length: 255, nullable: true)]
  #[OA\Property(description: "行为描述")]
  private ?string $action_desc = null;

  /**
   * ───────────────
   * 资源对象（On What）
   * ───────────────
   */
  #[Column(type: Types::STRING, length: 64, nullable: true, index: true)]
  #[OA\Property(description: "资源")]
  private ?string $resource = null;

  #[Column(type: Types::STRING, length: 64, nullable: true, index: true)]
  #[OA\Property(description: "资源ID")]
  private ?string $resource_id = null;

  #[Column(type: Types::JSON, nullable: true)]
  #[OA\Property(description: "附加上下文")]
  private ?array $context = null;

  /**
   * ───────────────
   * 请求环境（辅助溯源）
   * ───────────────
   */
  #[Column(type: Types::STRING, length: 10)]
  #[OA\Property(description: "HTTP 方法")]
  private string $method;

  #[Column(type: Types::STRING, length: 128)]
  #[OA\Property(description: "请求路由")]
  private string $route;

  #[Column(type: Types::STRING, length: 128)]
  #[OA\Property(description: "来源 IP")]
  private string $ip;

  #[Column(type: Types::STRING, length: 500)]
  #[OA\Property(description: "User-Agent")]
  private string $user_agent;

  /**
   * @return int|null
   */
  public function getId(): ?int
  {
    return $this->id;
  }

  /**
   * @param int|null $id
   * @return AuditLogEntity
   */
  public function setId(?int $id): AuditLogEntity
  {
    $this->id = $id;
    return $this;
  }

  /**
   * @return int
   */
  public function getEventTime(): int
  {
    return $this->event_time;
  }

  /**
   * @param int $event_time
   * @return AuditLogEntity
   */
  public function setEventTime(int $event_time): AuditLogEntity
  {
    $this->event_time = $event_time;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getRequestId(): ?string
  {
    return $this->request_id;
  }

  /**
   * @param string|null $request_id
   * @return AuditLogEntity
   */
  public function setRequestId(?string $request_id): AuditLogEntity
  {
    $this->request_id = $request_id;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getActorId(): ?string
  {
    return $this->actor_id;
  }

  /**
   * @param int|string|null $actor_id
   * @return AuditLogEntity
   */
  public function setActorId(int|string|null $actor_id): AuditLogEntity
  {
    $this->actor_id = $actor_id;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getClientId(): ?string
  {
    return $this->client_id;
  }

  /**
   * @param string|null $client_id
   * @return AuditLogEntity
   */
  public function setClientId(?string $client_id): AuditLogEntity
  {
    $this->client_id = $client_id;
    return $this;
  }

  /**
   * @return string
   */
  public function getActorType(): string
  {
    return $this->actor_type;
  }

  /**
   * @param string $actor_type
   * @return AuditLogEntity
   */
  public function setActorType(string $actor_type): AuditLogEntity
  {
    $this->actor_type = $actor_type;
    return $this;
  }

  /**
   * @return string
   */
  public function getAction(): string
  {
    return $this->action;
  }

  /**
   * @param string $action
   * @return AuditLogEntity
   */
  public function setAction(string $action): AuditLogEntity
  {
    $this->action = $action;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getActionDesc(): ?string
  {
    return $this->action_desc;
  }

  /**
   * @param string|null $action_desc
   * @return AuditLogEntity
   */
  public function setActionDesc(?string $action_desc): AuditLogEntity
  {
    $this->action_desc = $action_desc;
    return $this;
  }

  /**
   * @return string
   */
  public function getResource(): string
  {
    return $this->resource;
  }

  /**
   * @param string|null $resource
   * @return AuditLogEntity
   */
  public function setResource(string|null $resource): AuditLogEntity
  {
    $this->resource = $resource;
    return $this;
  }

  /**
   * @return string|null
   */
  public function getResourceId(): ?string
  {
    return $this->resource_id;
  }

  /**
   * @param int|string|null $resource_id
   * @return AuditLogEntity
   */
  public function setResourceId(int|string|null $resource_id): AuditLogEntity
  {
    $this->resource_id = $resource_id;
    return $this;
  }

  /**
   * @return array|null
   */
  public function getContext(): ?array
  {
    return $this->context;
  }

  /**
   * @param array|null $context
   * @return AuditLogEntity
   */
  public function setContext(?array $context): AuditLogEntity
  {
    $this->context = $context;
    return $this;
  }

  /**
   * @return string
   */
  public function getMethod(): string
  {
    return $this->method;
  }

  /**
   * @param string $method
   * @return AuditLogEntity
   */
  public function setMethod(string $method): AuditLogEntity
  {
    $this->method = $method;
    return $this;
  }

  /**
   * @return string
   */
  public function getRoute(): string
  {
    return $this->route;
  }

  /**
   * @param string $route
   * @return AuditLogEntity
   */
  public function setRoute(string $route): AuditLogEntity
  {
    $this->route = $route;
    return $this;
  }

  /**
   * @return string
   */
  public function getIp(): string
  {
    return $this->ip;
  }

  /**
   * @param string $ip
   * @return AuditLogEntity
   */
  public function setIp(string $ip): AuditLogEntity
  {
    $this->ip = $ip;
    return $this;
  }

  /**
   * @return string
   */
  public function getUserAgent(): string
  {
    return $this->user_agent;
  }

  /**
   * @param string $user_agent
   * @return AuditLogEntity
   */
  public function setUserAgent(string $user_agent): AuditLogEntity
  {
    $this->user_agent = $user_agent;
    return $this;
  }


}
