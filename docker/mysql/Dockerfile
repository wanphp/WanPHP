FROM mysql:latest

LABEL maintainer="火子 <wanphp@outlook.com>"

# 设置时区
RUN ln -snf /usr/share/zoneinfo/Asia/Shanghai /etc/localtime && echo "Asia/Shanghai" > /etc/timezone

# 自定义配置文件目录
COPY my.cnf /etc/mysql/conf.d/my.cnf
RUN chmod 0444 /etc/mysql/conf.d/my.cnf

# 初始化SQL（首次启动执行）
COPY initdb/ /docker-entrypoint-initdb.d/

# 备份脚本
COPY scripts/backup.sh /backup.sh
RUN chmod +x /backup.sh

# 暴露端口
EXPOSE 3306