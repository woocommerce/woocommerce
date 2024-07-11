#!/usr/bin/env bash

# Extract the relative path from the plugin root to this script directory.
# By doing so, we can run this script from anywhere.
script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
head_dir=$(cd "$(dirname "$script_dir")" && cd ../../.. && pwd)
relative_path=${script_dir#$head_dir/}

# Remove the database snapshot if it exists.
wp-env run tests-cli -- rm blocks_e2e.sql 2> /dev/null
# Run the main script in the container for better performance.
wp-env run tests-cli -- bash wp-content/plugins/woocommerce/blocks-bin/playwright/scripts/index.sh
# Disable the LYS Coming Soon banner.
wp-env run tests-cli -- wp option update woocommerce_coming_soon 'no'
