#!/bin/sh

rm -rf ./vendor-src
mv ./vendor ./vendor-src
./vendor-src/bin/php-scoper add-prefix
rm -rf ./vendor-src
