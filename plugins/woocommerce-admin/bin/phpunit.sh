#!/usr/bin/env bash
WORKING_DIR="$PWD"
cd "/tmp/wordpress"
ls -l
phpunit -c phpunit.xml.dist
cd "$WORKING_DIR"
