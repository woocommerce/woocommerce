#!/bin/sh

# When it is set to true, the commands are just printed but not executed.
DRY_RUN_MODE=false

# When it is set to true, the commands that affect the local env are executed (e.g. git commit), while the commands that affect the remote env are not executed but just printed (e.g. git push)
SIMULATE_RELEASE_MODE=false


# Output colorized strings
#
# Color codes:
# 0 - black
# 1 - red
# 2 - green
# 3 - yellow
# 4 - blue
# 5 - magenta
# 6 - cian
# 7 - white
output() {
  echo "$(tput setaf "$1")$2$(tput sgr0)"
}


simulate() {
  if $2 = true ; then
	eval "$1"
  else
	output 3 "DRY RUN: $1"
  fi
}


run_command() {
  if $DRY_RUN_MODE = true; then
	output 3 "DRY RUN: $1"
  elif $SIMULATE_RELEASE_MODE = true; then
		simulate "$1" $2
  else
	eval "$1"
  fi
}


run_command "rimraf ./storybook/dist/*" true
run_command "npm run storybook:build" true
run_command "gh-pages -d ./storybook/dist" false
