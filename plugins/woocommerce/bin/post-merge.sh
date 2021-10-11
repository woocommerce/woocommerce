#!/bin/bash

changedFiles="$(git diff-tree -r --name-only --no-commit-id ORIG_HEAD HEAD)"

runOnChange() {
	if echo "$changedFiles" | grep -q "$1"
	then
		eval "$2"
	fi
}

runOnChange "package-lock.json" "npm run install:no-e2e"
runOnChange "composer.lock" "SKIP_UPDATE_TEXTDOMAINS=true composer install"
