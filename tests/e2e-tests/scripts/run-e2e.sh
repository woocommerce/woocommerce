#!/usr/bin/env bash
#if [[ ${RUN_E2E} == 1 ]]; then
	set -ev
	# Setup
	export DISPLAY=:99.0
	sh -e /etc/init.d/xvfb start
 	sleep 3

	npm install
	export NODE_CONFIG_DIR="./tests/e2e-tests/config"

#	# Delete existing site if it exists and then create new
#	./tests/e2e-tests/scripts/wp-serverpilot-delete.js
#	./tests/e2e-tests/scripts/wp-serverpilot-init.js
#
#	# Import the encrypted SSH key
#	openssl aes-256-cbc -K $encrypted_aa1eba18da39_key -iv $encrypted_aa1eba18da39_iv -in tests/e2e-tests/scripts/deploy-key.enc -out deploy-key -d
#	chmod 600 deploy-key
#	mv deploy-key ~/.ssh/id_rsa
#
#	# Configure new server
#	scp -o "StrictHostKeyChecking no" tests/e2e-tests/scripts/sp-config.sh serverpilot@wp-e2e-tests.pw:~serverpilot/sp-config.sh
#	scp -o "StrictHostKeyChecking no" tests/e2e-tests/data/e2e-db.sql serverpilot@wp-e2e-tests.pw:~serverpilot/e2e-db.sql
#	ssh -o "StrictHostKeyChecking no" serverpilot@wp-e2e-tests.pw ~serverpilot/sp-config.sh "${TRAVIS_BRANCH}" wordpress-${TRAVIS_JOB_ID:0:20}

	#export BASE_URL="http://${TRAVIS_JOB_ID:0:20}.wp-e2e-tests.pw"

	#curl -I http://localhost
CONFIG_DIR="./tests/e2e-tests/config"
WP_CORE_DIR="$HOME/wordpress"
NGINX_DIR="$HOME/nginx"
mkdir -p "$WP_CORE_DIR"
mkdir -p "$NGINX_DIR"
mkdir -p "$NGINX_DIR/sites-enabled"
mkdir -p "$NGINX_DIR/var"

PHP_FPM_BIN="$HOME/.phpenv/versions/7.2/sbin/php-fpm"
PHP_FPM_CONF="$NGINX_DIR/php-fpm.conf"
cp "$CONFIG_DIR/travis_php-fpm.conf" "$PHP_FPM_CONF"
# Start php-fpm
"$PHP_FPM_BIN" --fpm-config "$PHP_FPM_CONF"
	# Copy the default nginx config files.
	cp "$CONFIG_DIR/travis_nginx.conf" "$NGINX_DIR/nginx.conf"
	cp "$CONFIG_DIR/travis_fastcgi.conf" "$NGINX_DIR/fastcgi.conf"
	cp "$CONFIG_DIR/travis_default-site.conf" "$NGINX_DIR/sites-enabled/default-site.conf"

	# Start nginx.
	nginx -c "$NGINX_DIR/nginx.conf"
	nginx -T

cd "$WP_CORE_DIR"
ls

curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
php wp-cli.phar core download
php wp-cli.phar core config --dbname=woocommerce_test --dbuser=root --dbpass='' --dbhost=localhost --dbprefix=wp_ --extra-php <<PHP
/* Change WP_MEMORY_LIMIT to increase the memory limit for public pages. */
define('WP_MEMORY_LIMIT', '256M');
PHP
php wp-cli.phar core install --url='http://localhost:8080' --title=Example --admin_user=admin --admin_password=password --admin_email=info@example.com
php wp-cli.phar db import ~/build/woocommerce/woocommerce/tests/e2e-tests/data/e2e-db.sql

php wp-cli.phar theme install twentytwelve --activate
php wp-cli.phar plugin install https://github.com/woocommerce/woocommerce/archive/$TRAVIS_BRANCH.zip --activate

cd ~/build/woocommerce/woocommerce

	export BASE_URL="http://localhost:8080"

	# Run the tests
	#npm test
#fi
