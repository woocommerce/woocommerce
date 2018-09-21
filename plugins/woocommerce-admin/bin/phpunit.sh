#!/usr/bin/env bash
WORKING_DIR="$PWD"
cd $HOME/wordpress/wp-content/plugin/wc-admin
phpunit -c phpunit.xml.dist
cd "$WORKING_DIR"
