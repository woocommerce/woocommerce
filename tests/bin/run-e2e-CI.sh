#!/usr/bin/env bash
if [[ ${RUN_E2E} == 1 ]]; then

	npm run test:e2e
fi
