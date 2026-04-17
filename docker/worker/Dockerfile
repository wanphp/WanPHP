FROM php:cli

RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    git \
    && docker-php-ext-install pdo pdo_mysql pcntl

# 安装 redis 扩展
RUN pecl install redis \
    && docker-php-ext-enable redis

WORKDIR /app