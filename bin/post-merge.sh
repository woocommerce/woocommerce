#!/bin/bash

changedFiles="$(git diff-tree -r --name-only --no-commit-id ORIG_HEAD HEAD)"

runOnChange() {
	if echo "$changedFiles" | grep -q "$1"
	then
		eval "$2"
	fi
}

runOnChange "pnpm-lock.yaml" "pnpm install"
runOnChange "composer.lock" "SKIP_UPDATE_TEXTDOMAINS=true composer install"
runOnChange "plugins/woocommerce/composer.lock" "SKIP_UPDATE_TEXTDOMAINS=true composer --working-dir=plugins/woocommerce install"
runOnChange "plugins/woocommerce-beta-tester/composer.lock" "SKIP_UPDATE_TEXTDOMAINS=true composer --working-dir=plugins/woocommerce-beta-tester install"
