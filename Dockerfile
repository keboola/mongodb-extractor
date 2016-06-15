FROM php:7.0
MAINTAINER Vladimír Kriška <vlado@keboola.com>
ENV DEBIAN_FRONTEND noninteractive

RUN apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv EA312927 \
  && echo 'deb http://repo.mongodb.org/apt/debian wheezy/mongodb-org/3.2 main' > /etc/apt/sources.list.d/mongodb-org-3.2.list \
  && apt-get update -q \
  && apt-get install unzip git libssl-dev mongodb-org-shell mongodb-org-tools ssh -y --no-install-recommends \
  && rm -rf /var/lib/apt/lists/*

RUN pecl install mongodb \
  && docker-php-ext-enable mongodb

WORKDIR /root

RUN curl -sS https://getcomposer.org/installer | php \
  && mv composer.phar /usr/local/bin/composer

COPY ./docker/php/php.ini /usr/local/etc/php/php.ini
COPY . /code

WORKDIR /code

RUN composer install --prefer-dist --no-interaction

CMD php ./src/run.php --data=/data
