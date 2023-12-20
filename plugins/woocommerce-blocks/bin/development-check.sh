#!/bin/bash

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

pass() {
	output 2 "$1"
}

fail() {
	output 1 "$1"
}

warn() {
	output 3 "$1"
}

function command_exists_as_alias {
	alias $1 2>/dev/null >/dev/null
}

function which {
	type "$1" >>/dev/null 2>&1
}

function command_is_available {
	which $1 || command_exists_as_alias $1 || type $1 >/dev/null 2>/dev/null
}

function node_modules_are_available {
	[ -d node_modules ]
}

function vendor_dir_is_available {
	[ -d vendor ]
}

function assert {
	$1 $2 && pass "- $3 is available ✔"
	$1 $2 || fail "- $3 is missing ✗ $4"
}

echo
output 6 "BLOCKS DEVELOPMENT ENVIRONMENT CHECKS"
output 6 "====================================="
echo
echo "Checking under $PWD"
echo
output 6 "(*・‿・)ノ⌒*:･ﾟ✧"

echo
echo "Tools for building assets"
echo "========================="
echo
assert command_is_available node "Node.js" "Node and NPM allow us to install required dependencies. You can install it from here: https://nodejs.org/en/download/"
assert command_is_available composer "Composer" "Composer allows us to install PHP dependencies. You can install it from https://getcomposer.org, or if you are running Brew you can install it by running $ brew install composer"

echo
echo "Dependencies"
echo "============"
echo
assert node_modules_are_available "" "node_modules dir" "You need to have node installed and run: $ npm install"
assert vendor_dir_is_available "" "vendor dir" "You need to have composer installed and run: $ composer install"

echo
echo "Contributing and other helpers"
echo "=============================="
echo
assert command_is_available git "git" "Git is required to push and pull from the GitHub repository. If you're running Brew, you can install it by running $ brew install git"
assert command_is_available hub "Hub" "Hub provides some useful git commands used by the deployment scripts.  If you're running Brew, you can install it by running $ brew install hub"
echo
