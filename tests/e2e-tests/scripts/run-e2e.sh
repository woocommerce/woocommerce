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

curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
php wp-cli.phar --info
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp
wp core download --locale=en_EN
wp core install --url=localhost --title=Example --admin_user=admin --admin_password=password --admin_email=info@example.com
wp core install
wp db import ~/e2e-db.sql

wp theme install twentytwelve --activate
wp plugin install https://github.com/woocommerce/woocommerce/archive/$1.zip --activate
	export BASE_URL="http://localhost"

	# Run the tests
	npm test
#fi
