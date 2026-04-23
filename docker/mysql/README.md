MYSQL_ROOT_PASSWORD=root用户密码 \
DATABASE_NAME=应用数据库名 \
DATABASE_USER=应用数据库用户名 \
DATABASE_USER_PASSWORD=应用数据库用户密码 \
DATABASE_ADMIN_PASSWORD=数据库开发管理用户密码 \
DATABASE_RD_PASSWORD=数据库查询密码

DATABASE_REPL_USER=主从复制用户名 \
DATABASE_REPL_PASSWORD=主从复制用户密码 

MASTER_DATABASE_HOST=主库host \
MASTER_DATABASE_PORT=主库端口

### 数据库备份
在宿主机加定时任务备份 \
0 3 * * * /usr/bin/docker exec mysql-slim /bin/bash /backup.sh >> /var/log/mysql_backup.log 2>&1
