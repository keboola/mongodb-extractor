FROM php:7
MAINTAINER Vladimír Kriška <vlado@keboola.com>

ENV DEBIAN_FRONTEND noninteractive

RUN apt-get update -q \
  && apt-get install unzip git mongodb-clients libssl-dev -y

RUN pecl install xdebug mongodb \
  && docker-php-ext-enable xdebug mongodb

RUN cd \
  && curl -sS https://getcomposer.org/installer | php \
  && ln -s /root/composer.phar /usr/local/bin/composer

ADD . /code

RUN cd /code \
  && composer install --prefer-dist --no-interaction

WORKDIR /code

CMD php ./src/run.php --data=/data
