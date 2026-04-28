FROM owasp/modsecurity-crs:4.25-nginx-alpine-lts

LABEL maintainer="火子 <wanphp@outlook.com>"

ARG TZ=Asia/Shanghai
ENV TZ=${TZ}
USER root
# 安装 tzdata + logrotate
RUN apk add --no-cache tzdata logrotate \
    && ln -snf /usr/share/zoneinfo/$TZ /etc/localtime \
    && echo $TZ > /etc/timezone

RUN addgroup -g 1000 -S nginx || true \
    && adduser -u 1000 -D -S -G nginx nginx || true

# 日志分割
COPY logrotate/nginx /etc/logrotate.d/nginx
RUN sed -i 's/\r$//' /etc/logrotate.d/nginx  && chmod 644 /etc/logrotate.d/nginx
# 自定义配置
COPY templates/conf.d/default.conf.template /etc/nginx/templates/conf.d/default.conf.template
COPY templates/conf.d/wanphp.conf.template /etc/nginx/templates/conf.d/wanphp.conf.template
# ssl加固
COPY templates/includes/ssl.conf.template /etc/nginx/templates/includes/ssl.conf.template
# PHP
COPY templates/includes/php-fpm.conf.template /etc/nginx/templates/includes/php-fpm.conf.template
# 安全头
COPY templates/includes/security_headers.conf /etc/nginx/includes/security_headers.conf
# 上传文件
COPY templates/includes/files.conf /etc/nginx/includes/files.conf
# 代理微信图片
COPY templates/includes/proxy_wx_image.conf /etc/nginx/includes/proxy_wx_image.conf
RUN chmod 0644 /etc/nginx/includes/*.conf

# 预设环境变量 (可在 docker-compose 中覆盖)
ENV BACKEND_PHP=php-fpm

