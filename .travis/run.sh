#!/bin/bash -e
if [[ "$COVERAGE" == "1" ]]; then
  echo;
  echo "Running unit tests with code-coverage";
  composer cover
  echo;
  echo "Uploading code coverage results";
  wget https://scrutinizer-ci.com/ocular.phar
  php ocular.phar code-coverage:upload --format=php-clover unittest-coverage.clover
else
  echo;
  echo "Running unit tests";
  composer test
fi
