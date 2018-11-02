#!/usr/bin/env bash

if [[ ${RUN_JS} == 1 ]]; then
	npm run lint
	npm run build
	npm test
fi
