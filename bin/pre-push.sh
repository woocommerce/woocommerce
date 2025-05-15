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

# Ensure the checks are running only when pushing a new branch or there are commits to push.
matchingRemoteBranches=$(git ls-remote --heads origin refs/heads/$CURRENT_BRANCH)
if [ -n "$matchingRemoteBranches" ]; then
	commitsToPush=$(git log origin/$CURRENT_BRANCH..$CURRENT_BRANCH)
	if [ -z "$commitsToPush" ]; then
		echo 'pre-push: Everything up-to-date, skipping validation and linting'
		exit 0
	fi
fi

git fetch origin trunk >/dev/null 2>&1
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
	if [ -n "$matchingRemoteBranches" ]; then
		# The remote branch exists: lint incremental changes only
		git fetch origin $CURRENT_BRANCH >/dev/null 2>&1
		ciJobs=$(CI=1 pnpm utils ci-jobs --base-ref origin/$CURRENT_BRANCH --event 'pull_request' 2>&1)
	else
		# The remote branch doesn't exists yes: lint all branch changes
		ciJobs=$(CI=1 pnpm utils ci-jobs --base-ref origin/trunk --event 'pull_request' 2>&1)
	fi

	# Slightly complicated trailing thru linting jobs provided in JSON-format.
    lintingJobs=$(echo $ciJobs | sed 's/::set-output/\n::set-output/g' | grep '::set-output name=lint-jobs::' | sed 's/::set-output name=lint-jobs:://g')
    iteration=1
    iterations=$( echo $lintingJobs | jq length )

    # Failsafe: running full-scale repo linting might occur occasionally - not clear why, hence this failsafe.
    if [ $iterations -ge 45 ]; then
    	echo "-> Looks like we were about to lint the whole monorepo, it might take a while so we are skipping this step [SKIP]"
    	echo "   Note: that's not necessary related to the changes - possibly we are behind the changes on remote."
    	exit 0
	fi

    while read job; do
		command=$(echo $job | jq --raw-output '( "pnpm --filter=" + .projectName + " " + .command )')
		echo -n "-> Executing '$command' ($iteration of $iterations) "
		start=$SECONDS
		result=$($command 2>&1)
		code=$?
		duration=$(( SECONDS - start ))
		if [ $code -ne 0 ]; then
			echo "[ERR] (aborting, please run manually to troubleshoot)"
			exit 1
		fi
		echo "($duration sec.) [OK]"
		iteration=$(expr $iteration + 1)
	done < <(echo $lintingJobs | jq --compact-output '.[]')
fi
