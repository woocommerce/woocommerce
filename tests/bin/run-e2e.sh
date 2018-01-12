#!/usr/bin/env bash
if [[ ${RUN_E2E} == 1 ]]; then
	set -ev
	npm install
	export NODE_CONFIG_DIR="./tests/e2e-tests/config"

	# Set up nginx to run the server
	CONFIG_DIR="./tests/e2e-tests/config/travis"
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

	# Set up WordPress using wp-cli
	cd "$WP_CORE_DIR"

	curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
	php wp-cli.phar core download
	php wp-cli.phar core config --dbname=woocommerce_test --dbuser=root --dbpass='' --dbhost=localhost --dbprefix=wp_ --extra-php <<PHP
/* Change WP_MEMORY_LIMIT to increase the memory limit for public pages. */
define('WP_MEMORY_LIMIT', '256M');
PHP
	php wp-cli.phar core install --url="http://localhost:8080" --title="Example" --admin_user=admin --admin_password=password --admin_email=info@example.com --path=$WP_CORE_DIR --skip-email
	php wp-cli.phar db import ~/build/woocommerce/woocommerce/tests/e2e-tests/data/e2e-db.sql
	php wp-cli.phar search-replace 'http://local.wordpress.test' 'http://localhost:8080'
	php wp-cli.phar theme install twentytwelve --activate
	php wp-cli.phar plugin install https://github.com/woocommerce/woocommerce/archive/$TRAVIS_BRANCH.zip --activate

	cd ~/build/woocommerce/woocommerce

	# Start xvfb to run the tests
	export BASE_URL="http://localhost:8080"
	export DISPLAY=:99.0
	sh -e /etc/init.d/xvfb start
 	sleep 3

	# Run the tests
	npm test
fi
