#!/bin/bash

set -eo pipefail

echo "Default environment (+ object cache plugin) setup."

# Command prefix for running wp-cli against the single-container test environment
# (started via `wp-env --config .wp-env.test.json`, whose container is `cli`).
wp_cli="pnpm wp-env:test run cli"

$wp_cli wp plugin install sqlite-object-cache --activate
