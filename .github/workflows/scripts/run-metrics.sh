#!/bin/bash

set -eo pipefail

if [[ -z "$GITHUB_EVENT_NAME" ]]; then
 	echo "::error::GITHUB_EVENT_NAME must be set"
 	exit 1
fi

function title() {
  echo -e "\n\033[1m$1\033[0m"
}

if [ "$GITHUB_EVENT_NAME" == "push" ] || [ "$GITHUB_EVENT_NAME" == "pull_request" ]; then
	# It should be 3d7d7f02017383937f1a4158d433d0e5d44b3dc9, but we pick 55f855a2e6d769b5ae44305b2772eb30d3e721df
	# where compare-perf reporting mode was introduced for processing the provided reports.
	BASE_SHA=55f855a2e6d769b5ae44305b2772eb30d3e721df
	echo "Comparing performance between: $BASE_SHA -> $GITHUB_SHA"

	WP_VERSION=$(awk -F ': ' '/^Tested up to/{print $2}' readme.txt)
	echo "WP_VERSION: $WP_VERSION"
	IFS=. read -ra WP_VERSION_ARRAY <<< "$WP_VERSION"

	title "Installing dependencies"
    # pnpm install --frozen-lockfile --filter="compare-perf" > /dev/null

  	title "Comparing performance: benchmarking"
	# TODO: benchmark for missing reports only.

  	title "Comparing performance: processing reports"
	# Updating the WP version used for performance jobs means there’s a high
	# chance that the reference commit used for performance test stability
	# becomes incompatible with the WP version. So, every time the "Tested up
	# to" flag is updated in the readme.txt, we also have to update the
	# reference commit below (BASE_SHA). The new reference needs to meet the
	# following requirements:
	# - Be compatible with the new WP version used in the “Tested up to” flag.
	# - Be tracked on https://www.codevitals.run/project/woo for all existing
	#   metrics.
	# pnpm --filter="compare-perf" run compare perf $GITHUB_SHA $BASE_SHA --tests-branch $GITHUB_SHA --wp-version "${WP_VERSION_ARRAY[0]}.${WP_VERSION_ARRAY[1]}"

	# title "Publish results to CodeVitals"
	# COMMITTED_AT=$(git show -s $GITHUB_SHA --format="%cI")
    # pnpm --filter="compare-perf" run log $CODEVITALS_PROJECT_TOKEN trunk $GITHUB_SHA $BASE_SHA $COMMITTED_AT
else
  	echo "Unsupported event: $GITHUB_EVENT_NAME"
fi
