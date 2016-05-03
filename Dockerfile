FROM php:7.0
MAINTAINER Vladimír Kriška <vlado@keboola.com>

ENV DEBIAN_FRONTEND noninteractive

RUN apt-key adv --keyserver hkp://keyserver.ubuntu.com:80 --recv EA312927 \
  && echo 'deb http://repo.mongodb.org/apt/debian wheezy/mongodb-org/3.2 main' > /etc/apt/sources.list.d/mongodb-org-3.2.list \
  && apt-get update -q \
  && apt-get install unzip git libssl-dev mongodb-org-shell mongodb-org-tools -y

RUN pecl install xdebug mongodb \
  && docker-php-ext-enable xdebug mongodb

RUN cd \
  && curl -sS https://getcomposer.org/installer | php \
  && ln -s /root/composer.phar /usr/local/bin/composer

RUN echo "memory_limit = -1" > /usr/local/etc/php/php.ini

ADD . /code

RUN cd /code \
  && composer install --prefer-dist --no-interaction

WORKDIR /code

CMD php ./src/run.php --data=/data
