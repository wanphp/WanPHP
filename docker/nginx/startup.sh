#!/bin/sh
set -e

(
  while true; do
    logrotate /etc/logrotate.conf
    sleep 86400
  done
) &

nginx -t

exec nginx -g "daemon off;"