### 日志分割
在宿主机加定时任务分割 \
0 0 * * * docker exec [容器ID或名称] logrotate -f /etc/logrotate.d/nginx

### 常量
BACKEND_PHP=[PHP容器名称] \
APP_UPLOAD_FILE_PATH=[上传文件路径] \
SERVER_NAME=[服务域名] \
SSL_CERT_FILE=[证书文件路径] \
SSL_CERT_KEY_FILE=[证书私钥文件路径] \