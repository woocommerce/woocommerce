#!/bin/bash

set -eo pipefail

PLUGIN_REPOSITORY='woocommerce/woocommerce-paypal-payments' PLUGIN_NAME='WooCommerce PayPal Payments' PLUGIN_SLUG=woocommerce-paypal-payments ../../bin/install-plugin.sh
