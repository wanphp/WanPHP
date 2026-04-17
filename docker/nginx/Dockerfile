FROM nginx:alpine

LABEL maintainer="火子 <wanphp@outlook.com>"

ARG TZ=Asia/Shanghai
ENV TZ=${TZ}

# 安装 tzdata + logrotate
RUN apk add --no-cache tzdata logrotate \
    && ln -snf /usr/share/zoneinfo/$TZ /etc/localtime \
    && echo $TZ > /etc/timezone

# 创建 www-data 用户
RUN addgroup -g 1000 -S www-data \
    && adduser -u 1000 -D -S -G www-data www-data

# 日志文件
COPY logrotate/nginx /etc/logrotate.d/
COPY nginx.conf /etc/nginx/

COPY startup.sh /opt/startup.sh
RUN chmod +x /opt/startup.sh \
    && sed -i 's/\r$//' /opt/startup.sh

EXPOSE 80 443

CMD ["sh", "/opt/startup.sh"]