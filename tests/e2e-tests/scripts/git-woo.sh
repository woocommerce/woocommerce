#!/bin/bash
export APP_NAME=$2
if [ "$APP_NAME" == "" ]; then
  echo "Please supply app directory name!"
  exit 1
elif [ ! -d apps/$APP_NAME/public/wp-content/plugins ]; then
  echo "App directory apps/$APP_NAME/public/wp-content/plugins does not exist."
  exit 1
fi

cd apps/$APP_NAME/public/

dbname=`wp config get --constant=DB_NAME`
dbuser=`wp config get --constant=DB_USER`
dbpass=`wp config get --constant=DB_PASSWORD`

wp db drop --yes

wp config create --dbname=$dbname --dbuser=$dbuser --dbpass=$dbpass --force --extra-php <<PHP
define('SP_REQUEST_URL', (\$_SERVER['HTTPS'] ? 'https://' : 'http://') . \$_SERVER['HTTP_HOST']);

define('WP_SITEURL', SP_REQUEST_URL);
define('WP_HOME', SP_REQUEST_URL);

/* Change WP_MEMORY_LIMIT to increase the memory limit for public pages. */
define('WP_MEMORY_LIMIT', '256M');
PHP

wp db create
wp db import ~/e2e-db.sql

wp theme install twentytwelve --activate
wget https://github.com/woocommerce/woocommerce/archive/$1.zip -O woocommerce.zip
wp plugin install woocommerce.zip --activate
wp plugin list

