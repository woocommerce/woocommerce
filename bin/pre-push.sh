#!/usr/bin/env bash

PROTECTED_BRANCH="trunk"
CURRENT_BRANCH=$(git branch --show-current)
if [ $PROTECTED_BRANCH = $CURRENT_BRANCH ]; then
	if [ "$TERM" = "dumb" ]; then
		>&2 echo "Sorry, you are unable to push to $PROTECTED_BRANCH using a GUI client! Please use git CLI."
		exit 1
	fi

	printf "%sYou're about to push to $PROTECTED_BRANCH, is that what you intended? [y/N]: %s" "$(tput setaf 3)" "$(tput sgr0)"
	read -r PROCEED < /dev/tty
	echo

	if [ "$(echo "${PROCEED:-n}" | tr "[:upper:]" "[:lower:]")" = "y" ]; then
		echo "$(tput setaf 2)Brace yourself! Pushing to the $PROTECTED_BRANCH branch...$(tput sgr0)"
		echo
		exit 0
	fi

	echo "$(tput setaf 2)Push to $PROTECTED_BRANCH cancelled!$(tput sgr0)"
	echo
	exit 1
fi

changedFiles=$(git diff $(git merge-base HEAD origin/trunk) --relative --name-only --diff-filter=d -- '.syncpackrc' 'package.json' '*/package.json')
if [ -n "$changedFiles" ]; then
	echo -n 'pre-push: validating syncpack mismatches '
	pnpm exec syncpack -- list-mismatches
	if [ $? -ne 0 ]; then
		echo "[ERR] (aborting)"
		echo "You must sync the dependencies listed above before you can push this branch."
		echo "This can usually be accomplished automatically by updating the pinned version in \`.syncpackrc\` and then running \`pnpm sync-dependencies\`."
		exit 1
	fi
	echo "[OK]"
fi

changedFiles=$(git diff $(git merge-base HEAD origin/trunk) --relative --name-only --diff-filter=d -- '*.php' '*.js' '*.jsx' '*.ts' '*.tsx')
if [ -n "$changedFiles" ]; then
	echo 'pre-push: linting changes (if unrelated linting occurs, please sync the branch with trunk)'
	# This pre-push check aims to reduce CI load, hence we mimic CI matrix generation and pick linting jobs identical to CI environment.
    ciJobs=$(CI=1 pnpm utils ci-jobs --base-ref origin/trunk --event 'pull_request' 2>&1)
    lintingJobs=$(echo $ciJobs | sed 's/::set-output/\n::set-output/g' | grep '::set-output name=lint-jobs::' | sed 's/::set-output name=lint-jobs:://g')
	# Slightly complicated trailing thru linting jobs provided in JSON-format.
    iteration=1
    iterations=$( echo $lintingJobs | jq length )
    while read job; do
		command=$(echo $job | jq --raw-output '( "pnpm --filter=" + .projectName + " " + .command )')
		echo -n "-> Executing '$command' ($iteration of $iterations) "
		result=$($command 2>&1)
		if [ $? -ne 0 ]; then
			echo "[ERR] (aborting, please run manually to troubleshoot)"
			exit 1
		fi
		echo "[OK]"
		iteration=$(expr $iteration + 1)
	done < <(echo $lintingJobs | jq --compact-output '.[]')
fi
