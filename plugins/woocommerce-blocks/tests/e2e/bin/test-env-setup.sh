#!/usr/bin/env bash

# Extract the relative path from the plugin root to this script directory.
# By doing so, we can run this script from anywhere.
script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
head_dir=$(cd "$(dirname "$script_dir")" && cd ../../.. && pwd)
relative_path=${script_dir#$head_dir/}

# Run the main script in the container for better performance.
wp-env run tests-cli -- bash wp-content/plugins/woocommerce/blocks-bin/playwright/scripts/index.sh
