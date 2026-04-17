FROM php:fpm

LABEL maintainer="火子 <wanphp@outlook.com>"

ARG TZ=Asia/Shanghai
ARG PUID=1000
ARG PGID=1000

ENV TZ=${TZ} \
    PUID=${PUID} \
    PGID=${PGID} \
    DEBIAN_FRONTEND=noninteractive

RUN set -xe; \
    ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone; \
    curl -sSL https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions -o /usr/local/bin/install-php-extensions; \
    chmod +x /usr/local/bin/install-php-extensions; \
    install-php-extensions gd zip pdo_mysql exif bcmath opcache redis mongodb imagick; \
    groupmod -o -g ${PGID} www-data; \
    usermod -o -u ${PUID} -g www-data www-data; \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false; \
    apt-get clean; \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/local/bin/install-php-extensions;

# 配置处理
RUN cp /usr/local/etc/php/php.ini-production /usr/local/etc/php/php.ini
COPY ./wanphp.ini /usr/local/etc/php/conf.d/wanphp.ini
COPY ./zwanphp.pool.conf /usr/local/etc/php-fpm.d/

WORKDIR /var/www
CMD ["php-fpm"]