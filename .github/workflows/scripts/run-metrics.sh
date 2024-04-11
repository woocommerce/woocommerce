#!/bin/bash

set -eo pipefail

if [[ -z "$GITHUB_EVENT_NAME" ]]; then
 	echo "::error::GITHUB_EVENT_NAME must be set"
 	exit 1
fi

if [[ "$GITHUB_EVENT_NAME" == "pull_request" ]]; then
  	echo "Comparing performance with trunk"
  	cd tools/compare-perf && pnpm run compare perf $GITHUB_SHA trunk --tests-branch $GITHUB_SHA

elif [[ "$GITHUB_EVENT_NAME" == "push" ]]; then
  	echo "Comparing performance with base branch"
	# The base hash used here need to be a commit that is compatible with the current WP version
	# The current one is 19f3d0884617d7ecdcf37664f648a51e2987cada
	# it needs to be updated every time it becomes unsupported by the current wp-env (WP version).
	# It is used as a base comparison point to avoid fluctuation in the performance metrics.
	WP_VERSION=$(awk -F ': ' '/^Tested up to/{print $2}' plugins/woocommerce/readme.txt)
	IFS=. read -ra WP_VERSION_ARRAY <<< "$WP_VERSION"
	WP_MAJOR="${WP_VERSION_ARRAY[0]}.${WP_VERSION_ARRAY[1]}"
	cd tools/compare-perf && pnpm run compare perf $GITHUB_SHA 19f3d0884617d7ecdcf37664f648a51e2987cada --tests-branch $GITHUB_SHA --wp-version "$WP_MAJOR"

	echo "Publish results to CodeVitals"
	COMMITTED_AT=$(git show -s $GITHUB_SHA --format="%cI")
    cd tools/compare-perf && pnpm run log $CODEVITALS_PROJECT_TOKEN trunk $GITHUB_SHA 19f3d0884617d7ecdcf37664f648a51e2987cada $COMMITTED_AT
else
  	echo "Unsupported event: $GITHUB_EVENT_NAME"
fi
