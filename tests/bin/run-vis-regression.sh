#!/usr/bin/env bash
if [[ ${RUN_VIS_REGRESSION} == 1 ]]; then

	WP_SITE_URL="http://localhost:8080"

	# Setup enviromental variables
	export BASE_URL="$WP_SITE_URL"

	# Run the tests
	npm test:vis-regression
fi
