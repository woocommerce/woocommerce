#!/usr/bin/env bash

# Remember starting dir
CWD="$PWD"

# Get total number of tests
cd "$GITHUB_WORKSPACE/plugins/woocommerce"
TOTAL_STR=$(pnpm test:e2e-pw --list | grep "Total:")
NO_PREFIX=${TOTAL_STR#*"Total: "}
COUNT=${NO_PREFIX%" tests in"*}

# Save total as output
echo "total=$COUNT" >> "$GITHUB_OUTPUT"

# Return to starting dir then exit
cd "$CWD"