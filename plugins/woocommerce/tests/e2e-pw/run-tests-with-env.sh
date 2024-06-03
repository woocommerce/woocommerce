#!/usr/bin/env bash

set -eo pipefail

envName=$1
shift

SCRIPT_PATH=$(
  cd "$(dirname "${BASH_SOURCE[0]}")" || return
  pwd -P
)

echo "Setting up environment: $envName"
"$SCRIPT_PATH/envs/$envName/env-setup.sh"

echo "Running tests with environment: '$envName'"
echo "Arguments: $*"
pnpm playwright test --config="$SCRIPT_PATH"/envs/"$envName"/playwright.config.js "$@"
