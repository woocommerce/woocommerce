#!/usr/bin/env bash

set -ex

PACKAGE_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )"/../ && pwd )"

download() {
    if [ `which curl` ]; then
        curl -s "$1" > "$2";
    elif [ `which wget` ]; then
        wget -nv -O "$2" "$1"
    fi
}

install_wp_cli() {

	# the Behat test suite will pick up the executable found in $WP_CLI_BIN_DIR
	mkdir -p $WP_CLI_BIN_DIR
	download https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli-nightly.phar $WP_CLI_BIN_DIR/wp
	chmod +x $WP_CLI_BIN_DIR/wp

}

download_behat() {

	cd $PACKAGE_DIR
	download https://getcomposer.org/installer installer
	php installer
	php composer.phar require --dev behat/behat='~2.5'

}

install_db() {
	mysql -e 'CREATE DATABASE IF NOT EXISTS wp_cli_test;' -uroot
	mysql -e 'GRANT ALL PRIVILEGES ON wp_cli_test.* TO "wp_cli_test"@"localhost" IDENTIFIED BY "password1"' -uroot
}

install_wp_cli
download_behat
install_db
