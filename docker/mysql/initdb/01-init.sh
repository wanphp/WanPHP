#!/bin/bash
set -euo pipefail

# 挂载了/backup-latest.sql.gz，按从库处理
if [ -f /backup/backup-latest.sql.gz ]; then
  if [ ! -s /backup/backup-latest.sql.gz ]; then
    echo "Backup file invalid!"
    exit 1
  fi
  echo "Replica init start..."

  echo "Importing compressed backup..."
  # 压缩包导入
  gunzip -c /backup/backup-latest.sql.gz | (echo "SET sql_log_bin = 0;"; cat) | mysql -uroot -p"$MYSQL_ROOT_PASSWORD"
  echo "Import done"

  echo "Configuring GTID replication..."
  mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<EOF
  STOP REPLICA;
  RESET REPLICA ALL;
  -- 如果导入的备份包含 GTID，确保从库状态干净
  RESET MASTER;

  CHANGE REPLICATION SOURCE TO
    SOURCE_HOST='${MASTER_DATABASE_HOST}',
    SOURCE_PORT=${MASTER_DATABASE_PORT},
    SOURCE_USER='${DATABASE_REPL_USER}',
    SOURCE_PASSWORD='${DATABASE_REPL_PASSWORD}',
    SOURCE_AUTO_POSITION=1;

  START REPLICA;
EOF
  echo "Replica ready"

else
  # =====================
  # 主库初始化逻辑
  # =====================
  echo "MySQL init start..."

  mysql -uroot -p"$MYSQL_ROOT_PASSWORD" <<EOF
  SET NAMES utf8mb4;

  -- 1. 创建数据库
  CREATE DATABASE IF NOT EXISTS \`${DATABASE_NAME}\`
  DEFAULT CHARACTER SET utf8mb4
  COLLATE utf8mb4_0900_ai_ci;

  -- 2. 创建业务用户 (caching_sha2_password 是 9.x 默认值，显式写出更好)
  CREATE USER IF NOT EXISTS '${DATABASE_USER}'@'%' IDENTIFIED WITH caching_sha2_password BY '${DATABASE_USER_PASSWORD}';
  GRANT SELECT, INSERT, UPDATE, DELETE ON \`${DATABASE_NAME}\`.* TO '${DATABASE_USER}'@'%';

  -- 3. 创建 Repl 用户
  $([ -n "${DATABASE_REPL_USER:-}" ] && echo "CREATE USER IF NOT EXISTS '${DATABASE_REPL_USER}'@'%' IDENTIFIED WITH caching_sha2_password BY '${DATABASE_REPL_PASSWORD}';")
  $([ -n "${DATABASE_REPL_USER:-}" ] && echo "GRANT REPLICATION SLAVE ON *.* TO '${DATABASE_REPL_USER}'@'%';")

  -- 4. 创建 Admin 用户
  $([ -n "${DATABASE_ADMIN_PASSWORD:-}" ] && echo "CREATE USER IF NOT EXISTS '${DATABASE_USER}_admin'@'%' IDENTIFIED BY '${DATABASE_ADMIN_PASSWORD}';")
  $([ -n "${DATABASE_ADMIN_PASSWORD:-}" ] && echo "GRANT ALL PRIVILEGES ON \`${DATABASE_NAME}\`.* TO '${DATABASE_USER}_admin'@'%';")

  -- 5. 创建 RO 用户
  $([ -n "${DATABASE_RO_PASSWORD:-}" ] && echo "CREATE USER IF NOT EXISTS '${DATABASE_USER}_ro'@'%' IDENTIFIED BY '${DATABASE_RO_PASSWORD}';")
  $([ -n "${DATABASE_RO_PASSWORD:-}" ] && echo "GRANT SELECT ON \`${DATABASE_NAME}\`.* TO '${DATABASE_USER}_ro'@'%';")

  FLUSH PRIVILEGES;
EOF
  echo "MySQL init done"
fi