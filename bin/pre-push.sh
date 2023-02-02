#!/bin/sh

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

pnpm exec syncpack -- list-mismatches

if [ $? -ne 0 ]; then
	echo "You must sync the dependencies listed above before you can push this branch."
	echo "This can usually be accomplished automatically by updating the pinned version in `.syncpackrc` and then running \`pnpm run sync-dependencies\`."
	exit 1
fi

# Ensure both branches are tracked or check-changelogger-use will fail.
git checkout $PROTECTED_BRANCH --quiet --no-verify
git checkout $CURRENT_BRANCH --quiet --no-verify

php tools/monorepo/check-changelogger-use.php $PROTECTED_BRANCH $CURRENT_BRANCH
