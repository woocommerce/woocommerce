#!/usr/bin/env bash
WORKING_DIR="$PWD"
cd "/tmp/wordpress/wp-content/plugins/wc-admin/"
phpunit -c phpunit.xml.dist
cd "$WORKING_DIR"
