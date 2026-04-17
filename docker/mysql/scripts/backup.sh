#!/bin/bash

DATE=$(date +%F_%H-%M-%S)
BACKUP_DIR=/backup

mkdir -p $BACKUP_DIR

mysqldump -uroot -p$MYSQL_ROOT_PASSWORD \
  --all-databases \
  --single-transaction \
  --set-gtid-purged=ON \
  > gzip > $BACKUP_DIR/backup-$DATE.sql.gz

# 更新 latest
ln -sf backup-$DATE.sql.gz $BACKUP_DIR/backup-latest.sql.gz

# 删除 7 天前
find $BACKUP_DIR -name "backup-*.sql.gz" -mtime +7 -delete

echo "Backup done"
