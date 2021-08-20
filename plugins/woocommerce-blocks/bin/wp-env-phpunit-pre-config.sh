#!/bin/sh
BASENAME=$(basename "`pwd`")

npm run wp-env run composer 'install --no-interaction'
npm run wp-env run phpunit 'php -v'
npm run wp-env run phpunit "phpunit -c /var/www/html/wp-content/plugins/${BASENAME}/phpunit.xml.dist --verbose"
