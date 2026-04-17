#!/bin/bash
set -euo pipefail

# 等 MySQL ready
until mysqladmin ping -uroot -p"$MYSQL_ROOT_PASSWORD" --silent; do
  echo "Waiting MySQL..."
  sleep 2
done

# MySQL 没有初始化过
if [ ! -d "/var/lib/mysql/mysql" ]; then
  # 挂载了backup.sql，按从库处理
  if [ -f /backup/backup.sql ]; then
    if [ ! -s /backup/backup.sql ]; then
      echo "Backup file invalid!"
      exit 1
    fi
    echo "Slave init start..."
    mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<-EOSQL
    SET sql_log_bin = 0;
    EOSQL

    echo "Importing backup..."
    mysql -uroot -p$MYSQL_ROOT_PASSWORD < /backup/backup.sql
    echo "Import done"

    echo "Configuring GTID replication..."
    mysql -uroot -p$MYSQL_ROOT_PASSWORD <<-EOSQL
    STOP SLAVE;
    RESET SLAVE ALL;

    CHANGE MASTER TO
      MASTER_HOST='${MASTER_DATABASE_HOST}',
      MASTER_PORT='${MASTER_DATABASE_PORT}',
      MASTER_USER='${DATABASE_REPL_USER}',
      MASTER_PASSWORD='${DATABASE_REPL_PASSWORD}',
      MASTER_AUTO_POSITION=1;

    START SLAVE;
    EOSQL
    echo "Slave ready"
  else
    echo "MySQL init start..."
    echo "DB: $DATABASE_NAME"
    echo "User: $DATABASE_USER"

    mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<-EOSQL

    SET NAMES utf8mb4;

    -- 创建数据库
    CREATE DATABASE IF NOT EXISTS \`${DATABASE_NAME}\`
    DEFAULT CHARACTER SET utf8mb4
    COLLATE utf8mb4_0900_ai_ci;

    -- 创建用户
    CREATE USER IF NOT EXISTS '${DATABASE_USER}'@'%'
    IDENTIFIED WITH caching_sha2_password BY '${DATABASE_USER_PASSWORD}';

    -- 授权，CRUD
    GRANT SELECT, INSERT, UPDATE, DELETE ON \`${DATABASE_NAME}\`.* TO '${DATABASE_USER}'@'%';

    FLUSH PRIVILEGES;

    EOSQL

    # =====================
    # Repl 用户（可选）
    # =====================
    if [ -n "${DATABASE_REPL_USER:-}" ] && [ -n "${DATABASE_REPL_PASSWORD:-}" ]; then
      echo "Creating repl user..."

      mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<-EOSQL
    -- 主从同步复制用户
    CREATE USER IF NOT EXISTS '${DATABASE_REPL_USER}'@'%' IDENTIFIED WITH caching_sha2_password BY '${DATABASE_REPL_PASSWORD}';
    GRANT REPLICATION SLAVE ON *.* TO '${DATABASE_REPL_USER}'@'%';
    FLUSH PRIVILEGES;

    EOSQL

    fi

    # =====================
    # Admin 用户（可选）
    # =====================
    if [ -n "${DATABASE_ADMIN_PASSWORD:-}" ]; then
      echo "Creating admin user..."

      mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<-EOSQL
    -- 管理用户
    CREATE USER IF NOT EXISTS '${DATABASE_USER}_admin'@'%'
    IDENTIFIED WITH caching_sha2_password BY '${DATABASE_ADMIN_PASSWORD}';

    GRANT ALL PRIVILEGES ON \`${DATABASE_NAME}\`.* TO '${DATABASE_USER}_admin'@'%';

    FLUSH PRIVILEGES;

    EOSQL
    fi

    # =====================
    # Read Only 用户（可选）
    # =====================
    if [ -n "${DATABASE_RO_PASSWORD:-}" ]; then
      echo "Creating read only user..."

      mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<-EOSQL
    -- 数据查询用户
    CREATE USER IF NOT EXISTS '${DATABASE_USER}_ro'@'%'
    IDENTIFIED WITH caching_sha2_password BY '${DATABASE_RO_PASSWORD}';

    GRANT SELECT ON \`${DATABASE_NAME}\`.* TO '${DATABASE_USER}_ro'@'%';

    FLUSH PRIVILEGES;

    EOSQL

    fi
    echo "MySQL init done"
  fi
fi