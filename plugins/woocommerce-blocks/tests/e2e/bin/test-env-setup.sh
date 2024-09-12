#!/usr/bin/env bash

# Remove the database snapshot if it exists.
wp-env run tests-cli -- rm -f blocks_e2e.sql
# Run the main script in the container for better performance.
wp-env run tests-cli -- bash wp-content/plugins/woocommerce/blocks-bin/playwright/scripts/index.sh
# Disable the LYS Coming Soon banner.
wp-env run tests-cli -- wp option update woocommerce_coming_soon 'no'
