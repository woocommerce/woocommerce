#!/bin/sh
BASENAME=$(basename "`pwd`")
# We need to pass the blocks plugin folder name to the script, the name can change depending on your local env and we can't hardcode it.
npm run wp-env run tests-cli './wp-content/plugins/woocommerce/blocks-bin/wp-env-config.sh' woocommerce
