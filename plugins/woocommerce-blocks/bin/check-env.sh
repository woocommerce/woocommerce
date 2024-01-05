#!/usr/bin/env bash
if ! docker info > /dev/null 2>&1; then
	echo "This script uses docker, and it isn't running - please start docker and try again!"
	exit 1
fi

if ! [ "$(docker ps --filter "name=wordpress" --filter "status=running" --quiet)" ]; then
	echo "This script uses wp-env, and it isn't running - please start wp-env and try again!"
	exit 1
fi
