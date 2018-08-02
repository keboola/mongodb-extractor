#!/bin/bash

php --version \
  && composer --version \
  && composer run-script phpcs \
  && composer run-script phpunit
