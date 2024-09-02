#!/bin/bash

set -eo pipefail

SCRIPT_PATH=$(
  cd "$(dirname "${BASH_SOURCE[0]}")" || return
  pwd -P
)

PLUGIN_REPOSITORY='automattic/woocommerce-services' PLUGIN_NAME='WooCommerce Shipping & Tax' PLUGIN_SLUG='woocommerce-services' "$SCRIPT_PATH"/../../bin/install-plugin.sh
