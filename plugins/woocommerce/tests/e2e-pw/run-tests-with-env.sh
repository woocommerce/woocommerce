#!/usr/bin/env bash

set -eo pipefail

envName=$1
shift

SCRIPT_PATH=$(
  cd "$(dirname "${BASH_SOURCE[0]}")" || return
  pwd -P
)

echo "Setting up environment: $envName"

cleanDotEnv=0

if [ -f "$SCRIPT_PATH/envs/$envName/.env.enc" ]; then
	echo "Found encrypted .env file for environment: $envName"
	"$SCRIPT_PATH/bin/dotenv.sh" -d "$SCRIPT_PATH/envs/$envName"
	cleanDotEnv=1
fi

"$SCRIPT_PATH/envs/$envName/env-setup.sh"

echo "Running tests with environment: '$envName'"
echo "Arguments: $*"
pnpm playwright test --config="$SCRIPT_PATH"/envs/"$envName"/playwright.config.js "$@"

if [ "$cleanDotEnv" -eq 1 ]; then
	echo "Cleaning up .env file"
	rm "$SCRIPT_PATH/.env"
fi
