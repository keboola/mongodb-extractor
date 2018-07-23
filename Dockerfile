FROM php:7.2
ENV DEBIAN_FRONTEND noninteractive
ENV COMPOSER_ALLOW_SUPERUSER 1

RUN apt-get update -q \
  && apt-get install unzip git libssl-dev ssh gnupg2 dirmngr -y --no-install-recommends \
  && apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv 9DA31620334BD75D9DCB49F368818C72E52529D4 \
  && echo "deb http://repo.mongodb.org/apt/debian stretch/mongodb-org/4.0 main" > /etc/apt/sources.list.d/mongodb-org-4.0.list \
  && apt-get update -q \
  && apt-get install mongodb-org-shell mongodb-org-tools -y \
  && rm -rf /var/lib/apt/lists/*

RUN pecl install mongodb \
  && docker-php-ext-enable mongodb

WORKDIR /root

RUN curl -sS https://getcomposer.org/installer | php \
  && mv composer.phar /usr/local/bin/composer

COPY ./docker/php/php-prod.ini /usr/local/etc/php/php.ini
COPY . /code

WORKDIR /code

RUN composer install --prefer-dist --no-interaction

CMD php ./src/run.php --data=/data
