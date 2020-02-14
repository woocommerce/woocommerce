#!/usr/bin/env bash

set -o errexit

if [[ ${RUN_JS} == 1 ]]; then
	npm run -s install-if-deps-outdated
	npm run lint
	npm run build
	npm test
fi
