#!/bin/bash

set -eo pipefail

# The commented variables are for troubleshooting locally. The commented commands below are also for local troubleshooting.
# GITHUB_EVENT_NAME='pull_request'
# GITHUB_SHA=$(git rev-parse HEAD)
# ARTIFACTS_PATH="$(realpath $(dirname -- ${BASH_SOURCE[0]})/../../../tools/compare-perf)/artifacts"

if [[ -z "$GITHUB_EVENT_NAME" ]]; then
 	echo "::error::GITHUB_EVENT_NAME must be set"
 	exit 1
fi

function title() {
  echo -e "\n\033[1m$1\033[0m"
}

if [ "$GITHUB_EVENT_NAME" == "push" ] || [ "$GITHUB_EVENT_NAME" == "pull_request" ]; then
	mkdir -p $ARTIFACTS_PATH && export WP_ARTIFACTS_PATH=$ARTIFACTS_PATH

	# It should be 3d7d7f02017383937f1a4158d433d0e5d44b3dc9, but we pick 55f855a2e6d769b5ae44305b2772eb30d3e721df
	# where compare-perf reporting mode was introduced for processing the provided reports.
	BASE_SHA=55f855a2e6d769b5ae44305b2772eb30d3e721df
	HEAD_BRANCH=$(git rev-parse --abbrev-ref HEAD)
	WP_VERSION=$(awk -F ': ' '/^Tested up to/{print $2}' readme.txt)
	title "Comparing performance between: $BASE_SHA@trunk (base) and $GITHUB_SHA@$HEAD_BRANCH (head) on WordPress v$WP_VERSION"

	title "##[group]Setting up necessary tooling"
	pnpm --filter="@woocommerce/plugin-woocommerce" test:e2e:install > /dev/null &
	pnpm install --filter='compare-perf...' --frozen-lockfile --config.dedupe-peer-dependents=false --ignore-scripts
	echo '##[endgroup]'

	if test -n "$(find $ARTIFACTS_PATH -maxdepth 1 -name "*_${GITHUB_SHA}_*" -print -quit)"; then
		title "Skipping benchmarking head as benchmarking results already available under $ARTIFACTS_PATH"
	else
		# title "##[group]Building head"
		# git -c core.hooksPath=/dev/null checkout --quiet $HEAD_BRANCH > /dev/null && echo 'On' $(git rev-parse HEAD)
		# pnpm run --if-present clean:build
		# pnpm install --filter='@woocommerce/plugin-woocommerce...' --frozen-lockfile --config.dedupe-peer-dependents=false
		# pnpm --filter='@woocommerce/plugin-woocommerce' build
		# echo '##[endgroup]'

		title "##[group]Benchmarking head"
		RESULTS_ID="editor_${GITHUB_SHA}_round-1" pnpm --filter="@woocommerce/plugin-woocommerce" test:metrics editor
		RESULTS_ID="product-editor_${GITHUB_SHA}_round-1" pnpm --filter="@woocommerce/plugin-woocommerce" test:metrics product-editor
		echo '##[endgroup]'
	fi

	if test -n "$(find $ARTIFACTS_PATH -maxdepth 1 -name "*_${BASE_SHA}_*" -print -quit)"; then
		title "Skipping benchmarking baseline as benchmarking results already available under $ARTIFACTS_PATH"
	else
		title "##[group]Checkout baseline"
		git fetch --no-tags --quiet --unshallow origin trunk
		echo '##[endgroup]'

		title "##[group]Building baseline"
		( git -c core.hooksPath=/dev/null checkout --quiet $BASE_SHA > /dev/null || git reset --hard $BASE_SHA ) && echo 'On' $(git rev-parse HEAD)
		pnpm run --if-present clean:build &
		pnpm install --filter='@woocommerce/plugin-woocommerce...' --frozen-lockfile --config.dedupe-peer-dependents=false
		pnpm --filter='@woocommerce/plugin-woocommerce' build
		echo '##[endgroup]'

		title "##[group]Benchmarking baseline"
		# This one is important: we run the same tests in the same state as we did at head benchmarking.
		git restore --source $GITHUB_SHA $(realpath $(dirname -- ${BASH_SOURCE[0]})/../../../plugins/woocommerce/tests)
		RESULTS_ID="editor_${BASE_SHA}_round-1" pnpm --filter="@woocommerce/plugin-woocommerce" test:metrics editor
		RESULTS_ID="product-editor_${BASE_SHA}_round-1" pnpm --filter="@woocommerce/plugin-woocommerce" test:metrics product-editor
		echo '##[endgroup]'

		# title "##[group]Restoring codebase state back to head"
		# git -c core.hooksPath=/dev/null checkout --quiet $HEAD_BRANCH > /dev/null && echo 'On' $(git rev-parse HEAD)
		# pnpm install --frozen-lockfile > /dev/null &
		# pnpm run --if-present clean:build
		# echo '##[endgroup]'
	fi

  	title "##[group]Processing reports under $ARTIFACTS_PATH"
  	ls -l $ARTIFACTS_PATH
	# Updating the WP version used for performance jobs means there’s a high
	# chance that the reference commit used for performance test stability
	# becomes incompatible with the WP version. So, every time the "Tested up
	# to" flag is updated in the readme.txt, we also have to update the
	# reference commit below (BASE_SHA). The new reference needs to meet the
	# following requirements:
	# - Be compatible with the new WP version used in the “Tested up to” flag.
	# - Be tracked on https://www.codevitals.run/project/woo for all existing
	#   metrics.
	IFS=. read -ra WP_VERSION_ARRAY <<< "$WP_VERSION"
	pnpm --filter="compare-perf" run compare perf $GITHUB_SHA $BASE_SHA --tests-branch $GITHUB_SHA --wp-version "${WP_VERSION_ARRAY[0]}.${WP_VERSION_ARRAY[1]}" --ci --skip-benchmarking
	echo '##[endgroup]'

	if [[ "$GITHUB_EVENT_NAME" == "push" ]]; then
		title "##[group]Publish results to CodeVitals"
		COMMITTED_AT=$(git show -s $GITHUB_SHA --format="%cI")
		pnpm --filter="compare-perf" run log $CODEVITALS_PROJECT_TOKEN trunk $GITHUB_SHA $BASE_SHA $COMMITTED_AT
		echo '##[endgroup]'
	fi
else
  	echo "Unsupported event: $GITHUB_EVENT_NAME"
fi
