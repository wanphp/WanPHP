#!/bin/bash
# 遇到错误立即退出
set -euo pipefail

DATE=$(date +%F_%H-%M-%S)
BACKUP_DIR="/backup"
BACKUP_FILE="$BACKUP_DIR/backup-$DATE.sql.gz"

mkdir -p "$BACKUP_DIR"

echo "Starting backup at $(date)..."

# 使用管道符号 | 配合 gzip
# --single-transaction: 保证 InnoDB 表的一致性快照且不锁表
# --set-gtid-purged=ON: 导出 GTID 信息，非常适合用于创建从库
# --all-databases: 备份所有库
mysqldump -uroot -p"$MYSQL_ROOT_PASSWORD" \
  --all-databases \
  --single-transaction \
  --set-gtid-purged=ON \
  --routines \
  --triggers \
  --events \
  | gzip > "$BACKUP_FILE"

# 检查备份是否成功
if [ ${PIPESTATUS[0]} -ne 0 ]; then
    echo "Error: mysqldump failed!"
    exit 1
fi

# 更新 latest
# 删除旧的最新指向文件（如果存在）
rm -f "$BACKUP_DIR/backup-latest.sql.gz"
# 创建硬链接
ln "$BACKUP_FILE" "$BACKUP_DIR/backup-latest.sql.gz"

# 删除 7 天前的旧备份
find "$BACKUP_DIR" -name "backup-*.sql.gz" -mtime +7 -exec rm {} \;

echo "[$(date)] Backup success: backup-$DATE.sql.gz"