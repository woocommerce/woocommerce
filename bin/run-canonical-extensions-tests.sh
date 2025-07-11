#!/usr/bin/env bash

# Use-cases for this script:
# - Testing WooCommerce core RCs: passing tests allows to reduce the number of teams involved and simplify coordination
# - Testing new WordPress releases: passing tests allows to reduce the number of teams involved and simplify coordination

echo 'Notice: If the testing focuses on a new WooCommerce version, please note that QIT E2E/API test synchronization is not instantaneous (as of June 2025).'
echo '        If unsure, reach out to #qit for clarifications or run the tests for a single repository and ensure E2E/API tests are not failing with the "Invalid parameter(s): woocommerce_version" error.'
echo ''

# Request and sanitize testing parameters inputs.
read -r -p "Which WooCommerce version should we use for testing (e.g., 9.9.0-rc.1, 9.9.0, nightly, rc or stable)?: " version
read -r -p "Which WordPress version should we use for testing (e.g., 6.8, latest or empty to use defaults)?: " wordpress
read -r -p "Which PHP version should we use for testing (e.g., 7.4, 8.4 or empty to use defaults)?: " php
read -r -p "Which GitHub repositories needs to be tested (e.g. https://github.com/woocommerce/woocommerce, space separated list or empty to use defaults)?: " -a repositories
if [[ ${#repositories[@]} -eq 0 ]]; then
	# Fetch canonical extensions list: needs access privileges higher that 'Maintain' to work - therefore it fallback strategy.
	file='/tmp/WOOCOMMERCE_CANONICAL_EXTENSIONS'
	echo -n 'Fetching extensions list: ';
	# The variable can be actualized under https://github.com/woocommerce/woocommerce/settings/variables/actions (mix of public and private repository URLs)
	( gh variable get CANONICAL_EXTENSIONS > $file && echo 'done' ) || ( echo 'error' && exit 1 )
	repositories=( $( cat $file | tr -d '\r' | tr '\n' ' ' ) )
fi

echo -n 'Verifying inputs: ';
file='/tmp/WOOCOMMERCE_QIT_ENVIRONMENTS'
curl -s 'https://qit.woo.com/wp-json/cd/v1/environment' --output $file || ( echo 'error (fetch available environments information)' && exit 1 )
if [[ $( cat $file | jq "(.woocommerce_versions | index(\"$version\"))" ) == 'null' ]]; then
  echo 'error (invalid WooCommerce version, see https://qit.woo.com/wp-json/cd/v1/environment )' && exit 1
fi
if [[ $wordpress != '' ]] && [[ $( cat $file | jq "(.wordpress_versions | index(\"$wordpress\"))" ) == 'null' ]]; then
  echo 'error (invalid WordPress version, see https://qit.woo.com/wp-json/cd/v1/environment )' && exit 1
fi
if [[ $php != '' ]] && [[ $( cat $file | jq "(.php_versions | has(\"$php\"))" ) == 'false' ]]; then
  echo 'error (invalid PHP version, see https://qit.woo.com/wp-json/cd/v1/environment )' && exit 1
fi
echo 'ok'

# Sort out which repositories provide the necessary workflows first.
filtered=()
skipped=()
echo -n "Looking up repositories (${#repositories[@]}): "
for repository in ${repositories[@]}; do
	repository=${repository%/}
	match=$( ( gh workflow list --json path --jq '.[].path' --repo $repository | grep -E '.github/workflows/(manual-ci.yml|ci-manual.yml)' | wc -l | tr -d '[:space:]' ) || echo '0' )
	if [[ $match == '1' ]]; then
		filtered+=( $repository )
	else
		skipped+=( $repository )
	fi
	echo -n '.'
done
filtered=( $( printf '%s\n' "${filtered[@]}" | sort ) )
skipped=( $( printf '%s\n' "${skipped[@]}" | sort ) )
echo ''

# Report the skipped repositories.
echo "Skipping due to missing target workflows or access permissions (${#skipped[@]} repo(s))"
for repository in ${skipped[@]}; do
	echo "    -- ${repository##*/}"
done

# Run checks for the target repositories.
running=()
echo "Launching checks (${#filtered[@]} repo(s))"
for repository in ${filtered[@]}; do
	echo -n "    -- ${repository##*/} :"

	# Report identified workflow details.
	workflow=$( gh workflow list --json path,id --repo $repository | jq --compact-output '.[]' | grep -E '.github/workflows/(manual-ci.yml|ci-manual.yml)' )
	workflow_path=$( echo $workflow | jq --raw-output '( .path )' )
	workflow_id=$( echo $workflow | jq --raw-output '( .id )' )
	echo -n " workflow ${workflow_path} (#${workflow_id})"

	# Report last run details.
	previous_run=$( gh api -H "Accept: application/vnd.github+json" -H "X-GitHub-Api-Version: 2022-11-28" /repos/${repository##https://github.com/}/actions/workflows/${workflow_id}/runs?per_page=1 --jq '.workflow_runs.[].id' )
	echo -n " previous run #${previous_run} "

	# Start a new run and report back.
	( echo "{\"wc-version\":\"$version\", \"wp-version\":\"$wordpress\", \"php-version\":\"$php\", \"qit-tests\":\"WooCommerce Pre-Release Tests (includes Activation, WooCommerce E2E and API tests)\"}" | gh workflow run ${workflow_id} --json --repo $repository >/dev/null 2>&1) || echo -n '[insufficient permissions] '
	for i in {1..10}; do
	    echo -n '.' && sleep 1s
	    last_run=$( gh api -H "Accept: application/vnd.github+json" -H "X-GitHub-Api-Version: 2022-11-28" /repos/${repository##https://github.com/}/actions/workflows/${workflow_id}/runs?per_page=1 --jq '.workflow_runs.[].id' )
	    if [[ $last_run != $previous_run ]]; then
            running+=( "$repository;${last_run}" )
            echo -n " new run #${last_run}"
            break
	    fi
	done

	echo ''
done

echo "Waiting for completion (${#running[@]} run(s), 1 min check interval, takes at least 40 min):"
result=()
while [ ${#running[@]} -gt 0 ]; do
	echo '    .'
	sleep 1m
	temp=()
	for entry in ${running[@]}; do
		fragments=( ${entry//;/ } )
		repository=${fragments[0]}
		id=${fragments[1]}
		status=$( gh api -H "Accept: application/vnd.github+json" -H "X-GitHub-Api-Version: 2022-11-28" /repos/${repository##https://github.com/}/actions/runs/$id --jq '.status' )
		# https://docs.github.com/en/pull-requests/collaborating-with-pull-requests/collaborating-on-repositories-with-code-quality-features/about-status-checks#check-statuses-and-conclusions
		if [[ $status == 'completed' ]] || [[ $status == 'failure' ]] || [[ $status == 'startup_failure' ]]; then
			if [[ $status == 'completed' ]]; then
				conclusion=$( gh api -H "Accept: application/vnd.github+json" -H "X-GitHub-Api-Version: 2022-11-28" /repos/${repository##https://github.com/}/actions/runs/$id --jq '.conclusion' )
				status="$status:$conclusion"
			fi
			echo "    ✓ ${repository##*/} ($status)"
			result+=( "$entry;$status" )
		else
			temp+=( $entry )
		fi
	done
	running=( "${temp[@]}" )
done
echo ''

echo "All runs completed:"
for entry in ${result[@]}; do
	fragments=( ${entry//;/ } )
	if [ -z ${CI+y} ]; then
		echo "    -- ${fragments[0]##*/} : status ${fragments[2]} (${fragments[0]}/actions/runs/${fragments[1]})"
	else
		echo "    -- ${fragments[0]##*/} : status ${fragments[2]} (https://github.com/*/*/actions/runs/${fragments[1]})"
	fi
done
