#!/bin/bash

set -eo pipefail

echo "Default environment (+ object cache plugin) setup."

pnpm wp-env run tests-cli wp plugin install sqlite-object-cache --activate
