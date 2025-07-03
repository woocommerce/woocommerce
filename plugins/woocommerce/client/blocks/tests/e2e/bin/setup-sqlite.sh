#!/usr/bin/env bash

# This script sets up the SQLite database for WordPress e2e tests.
#
# 1. Download and unzip the SQLite integration plugin.
curl -L https://github.com/WordPress/sqlite-database-integration/archive/refs/heads/develop.zip -o sqlite-database-integration.zip
unzip -o sqlite-database-integration.zip
mv sqlite-database-integration-develop wp-content/plugins/sqlite-database-integration
rm sqlite-database-integration.zip

# 2. Create the database directory.
mkdir -p wp-content/database

# 3. Copy the db.php file.
cp wp-content/plugins/sqlite-database-integration/db.copy wp-content/db.php

# 4. Install WordPress
wp core install --url=http://localhost:8889 --title=blocks --admin_user=admin --admin_password=password --admin_email=admin@example.com

wp plugin activate woocommerce wordpress-importer master/basic-auth.php
