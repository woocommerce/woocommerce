#!/usr/bin/env bash

set -eo pipefail

skipEnvSetup=False

while getopts "x" opt; do
  case $opt in
    x)
      skipEnvSetup=True
      ;;
    \?)
      echo "Invalid option: -$OPTARG" >&2
      exit 1
      ;;
  esac
done

shift $((OPTIND-1))

envName=$1
shift

SCRIPT_PATH=$(
  cd "$(dirname "${BASH_SOURCE[0]}")" || return
  pwd -P
)

title() {
    local text=${1:+ }$1${1:+ }
    local total=$((80 - ${#text}))
    local left=$((total / 2))
    local right=$((total - left))

    printf "%s%s%s\n" \
        "$(printf '=%.0s' $(seq 1 $left))" \
        "$text" \
        "$(printf '=%.0s' $(seq 1 $right))"
}

echo
title
title "Preparing to run tests with environment: $envName"
title

if [ -f "$SCRIPT_PATH/envs/$envName/.env.enc" ]; then
	echo "Found an encrypted .env file for environment '$envName'"
	"$SCRIPT_PATH/bin/dotenv.sh" -d "$envName"
elif [ -f "$SCRIPT_PATH/envs/$envName/.env" ]; then
	echo "Found a non encrypted .env file for environment '$envName'. Will copy and overwrite the main .env (if it exists)"
	cp "$SCRIPT_PATH/envs/$envName/.env" "$SCRIPT_PATH/.env"
else
	echo "No encrypted .env file found for environment '$envName'."
	echo "Removing .env file if it exists."
	rm -f "$SCRIPT_PATH/.env"
fi

if [ "$skipEnvSetup" == "True" ]; then
	echo "Skipping environment setup"
else
	echo "Executing environment setup script(s)"
	"$SCRIPT_PATH/envs/$envName/env-setup.sh"
fi

echo
title
title "Running tests with environment: '$envName'"
title

configFile="$SCRIPT_PATH/envs/$envName/playwright.config.js"
echo "Using config file: $configFile"
echo "Arguments: $*"
title

pnpm playwright test --config="$configFile" "$@"


