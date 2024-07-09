#!/bin/bash

set -eo pipefail

SCRIPT_PATH=$(
  cd "$(dirname "${BASH_SOURCE[0]}")" || return
  pwd -P
)

PLUGIN_REPOSITORY='woocommerce/woocommerce-paypal-payments' PLUGIN_NAME='WooCommerce PayPal Payments' PLUGIN_SLUG=woocommerce-paypal-payments "$SCRIPT_PATH"/../../bin/install-plugin.sh
