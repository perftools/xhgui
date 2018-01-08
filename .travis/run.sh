#!/bin/bash
if [[ "$COVERAGE" == "1" ]]; then
  echo;
  echo "Running unit tests with code-coverage";
  phpunit --coverage-clover=unittest-coverage.clover
  echo;
  echo "Uploading code coverage results";
  wget https://scrutinizer-ci.com/ocular.phar
  php ocular.phar code-coverage:upload --format=php-clover unittest-coverage.clover
else
  echo;
  echo "Running unit tests";
  phpunit
fi
