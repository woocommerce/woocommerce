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
		echo 'pre-push: Everything up-to-date, skipping checks'
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

# Once upon a time, we added linting here, aiming to reduce pressure on CI (failing lints required pushing new changes and rerunning CI).
# It added rather more friction for day-to-day development. Hence, note for future reference: there have already been at least two failed iterations.
# If you consider the next one, please find a more impactful automation task to look into or add extra 'x' in 'xx' to further count attempts.
