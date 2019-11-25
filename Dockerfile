FROM php:7.2
RUN apt-get update && apt-get install -y zlib1g-dev zip unzip
RUN docker-php-ext-install zip pdo pdo_mysql
RUN pecl install ds
RUN docker-php-ext-enable ds
