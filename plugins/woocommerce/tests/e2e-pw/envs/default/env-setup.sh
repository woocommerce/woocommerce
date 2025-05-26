#!/bin/bash

set -eo pipefail

echo "Default environment setup."

pnpm wp-env run tests-cli wp plugin install sqlite-object-cache --activate
