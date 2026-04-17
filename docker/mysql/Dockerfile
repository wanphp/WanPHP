FROM mysql:latest

LABEL maintainer="火子 <wanphp@outlook.com>"

# 设置环境变量（可覆盖）
ENV MYSQL_ROOT_PASSWORD=wanphp#1314 \
    DATABASE_NAME=wanphp \
    DATABASE_USER=wanphp \
    DATABASE_USER_PASSWORD=wanphp@1314 \

# 设置时区
RUN ln -snf /usr/share/zoneinfo/Asia/Shanghai /etc/localtime && echo "Asia/Shanghai" > /etc/timezone

# 自定义配置文件目录
COPY my.cnf /etc/mysql/conf.d/my.cnf
RUN chmod 0444 /etc/mysql/conf.d/my.cnf

# 初始化SQL（首次启动执行）
COPY initdb/ /docker-entrypoint-initdb.d/

# 日志目录（可选）
RUN mkdir -p /var/log/mysql \
    && chown -R mysql:mysql /var/log/mysql

# 备份脚本
COPY scripts/backup.sh /backup.sh
RUN chmod +x /backup.sh

# 暴露端口
EXPOSE 3306

# 启动（官方入口）
CMD ["mysqld"]