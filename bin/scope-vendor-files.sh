#!/bin/sh

set -e
./vendor/bin/php-scoper add-prefix --working-dir vendor --config ../scoper.inc.php
rm -rf ./vendor-src
mv ./vendor ./vendor-src
mv ./vendor-scoped ./vendor
cp -a ./vendor-src/bin/ ./vendor/bin/

