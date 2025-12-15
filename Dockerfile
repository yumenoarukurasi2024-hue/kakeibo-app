FROM php:8.2-apache

# 必要なライブラリをインストールしてからpdo_mysqlを有効化
RUN apt-get update && apt-get install -y \
    default-mysql-client \
    libmysqlclient-dev \
    && docker-php-ext-install pdo pdo_mysql

# ソースコードをコピー
COPY . /var/www/html/

# Apacheの設定
EXPOSE 80
