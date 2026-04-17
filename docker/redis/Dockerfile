FROM redis:7.2-alpine

LABEL maintainer="WanPHP <wanphp@outlook.com>"

# 复制自定义配置
COPY redis.conf /usr/local/etc/redis/redis.conf
COPY docker-entrypoint.sh /usr/local/bin/

RUN chmod +x /usr/local/bin/docker-entrypoint.sh

VOLUME /data

EXPOSE 6379

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["redis-server", "/usr/local/etc/redis/redis.conf"]