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

wp config create --dbname=$dbname --dbuser=$dbuser --dbpass=$dbpass --force
wp db create

wp theme install twentytwelve --activate
wget https://github.com/woocommerce/woocommerce/archive/$1.zip -O woocommerce.zip
wp plugin install woocommerce.zip
wp db import ~/e2e-db.sql
wp plugin activate --all
wp plugin list

