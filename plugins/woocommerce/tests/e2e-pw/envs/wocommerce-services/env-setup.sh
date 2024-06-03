#!/bin/bash

set -eo pipefail

PLUGIN_REPOSITORY='automattic/woocommerce-services' PLUGIN_NAME='WooCommerce Shipping & Tax' PLUGIN_SLUG='woocommerce-services' ../../bin/install-plugin.sh
