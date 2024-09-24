#!/usr/bin/env bash

# Set the database version to 11.2.4-jammy.
# See https://github.com/WordPress/gutenberg/issues/62242
sed -i -e "s/image: 'mariadb:lts'/image: 'mariadb:11.2.4-jammy'/g" node_modules/@wordpress/env/lib/build-docker-compose-config.js
