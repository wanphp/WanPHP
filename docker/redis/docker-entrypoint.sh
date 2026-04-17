#!/bin/sh
set -e

if [ -z "$REDIS_PASSWORD" ]; then
  echo "ERROR: REDIS_PASSWORD not set"
  exit 1
fi

# 替换 redis.conf 中的占位符
sed -i "s|\${REDIS_PASSWORD}|$REDIS_PASSWORD|g" /usr/local/etc/redis/redis.conf

exec "$@"