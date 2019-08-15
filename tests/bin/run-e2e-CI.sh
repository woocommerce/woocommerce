#!/usr/bin/env bash
if [[ ${RUN_E2E} == 1 ]]; then

	WP_SITE_URL="http://localhost:8080"

	# Set base url to that of e2e test suite.
	export BASE_URL="$WP_SITE_URL"

	# Run the tests
	npm test
fi
