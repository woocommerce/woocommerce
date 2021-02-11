#!/bin/bash

changedFiles="$(git diff-tree -r --name-only --no-commit-id ORIG_HEAD HEAD)"

runOnChange() {
	if echo "$changedFiles" | grep -q "$1"
	then
		eval "$2"
	fi
}

runOnChange "package-lock.json" "npm install"
runOnChange "composer.lock" "composer install"
