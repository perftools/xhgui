#!/bin/bash
if [[ "$COVERAGE" == "0" ]]; then
  echo;
  echo "Running unit tests";
  phpunit
fi

if [[ "$COVERAGE" == "1" ]]; then
  echo;
  echo "Running unit tests with code-coverage";
  phpunit --coverage-clover=unittest-coverage.clover
fi

if [[ "$COVERAGE" == "1" ]]; then
  echo;
  echo "Uploading code coverage results";
  wget https://scrutinizer-ci.com/ocular.phar
  php ocular.phar code-coverage:upload --format=php-clover unittest-coverage.clover
fi
