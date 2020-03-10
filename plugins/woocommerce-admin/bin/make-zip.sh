#!/bin/sh
#
# Build a installable plugin zip

# Output colorized strings
#
# Color codes:
# 0 - black
# 1 - red
# 2 - green
# 3 - yellow
# 4 - blue
# 5 - magenta
# 6 - cian
# 7 - white
output() {
  echo "$(tput setaf "$1")$2$(tput sgr0)"
}

output 2 "Creating archive... 🎁"

ZIP_FILE=$1

# Folders listed first then individual files
zip -r ${ZIP_FILE} . \
	-x \
	.git/\* \
	tests/\* \
	bin/\* \
	config/\* \
	node_modules/\* \
	vendor/bin/\* \
	vendor/dealerdirect/\* \
	vendor/doctrine/\* \
	vendor/phar-io/\* \
	vendor/phpcompatibility/\* \
	vendor/phpdocumentor/\* \
	vendor/phpspec/\* \
	vendor/phpunit/\* \
	vendor/sebastian/\* \
	vendor/squizlabs/\* \
	vendor/theseer/\* \
	vendor/webmozart/\* \
	vendor/woocommerce/\* \
	vendor/wp-coding-standards/\* \
	.distignore \
	.editorconfig \
	.gitignore \
	.gitlab-ci.yml \
	.travis.yml \
	.DS_Store \
	.zipignore \
	Thumbs.db \
	behat.yml \
	circle.yml \
	composer.json \
	composer.lock \
	Gruntfile.js \
	package.json \
	package-lock.json \
	phpunit.xml \
	phpunit.xml.dist \
	multisite.xml \
	multisite.xml.dist \
	phpcs.xml \
	phpcs.xml.dist \
	README.md \
	wp-cli.local.yml \
	yarn.lock \
	*.sql \
	*.tar.gz \
	*.zip
