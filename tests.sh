#!/bin/bash

php --version \
  && composer --version \
  && ./vendor/bin/phpcs --standard=psr2 -n --ignore=vendor . \
  && ./vendor/bin/phpunit
