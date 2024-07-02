#!/usr/bin/env bash

set -eo pipefail

envName=$1
shift

SCRIPT_PATH=$(
  cd "$(dirname "${BASH_SOURCE[0]}")" || return
  pwd -P
)

echo "Setting up environment: $envName"


if [ -f "$SCRIPT_PATH/envs/$envName/.env.enc" ]; then
	echo "Found encrypted .env file for environment '$envName'"
	"$SCRIPT_PATH/bin/dotenv.sh" -d "$envName"
else
	echo "No encrypted .env file found for environment '$envName'."
	echo "Removing .env file if it exists."
	rm -f "$SCRIPT_PATH/.env"
fi

"$SCRIPT_PATH/envs/$envName/env-setup.sh"

echo "Running tests with environment: '$envName'"
echo "Arguments: $*"
pnpm playwright test --config="$SCRIPT_PATH"/envs/"$envName"/playwright.config.js "$@"
