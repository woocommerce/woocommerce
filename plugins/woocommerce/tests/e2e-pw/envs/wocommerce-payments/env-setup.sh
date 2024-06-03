#!/bin/bash

set -eo pipefail

PLUGIN_REPOSITORY='automattic/woocommerce-payments' PLUGIN_NAME=WooPayments PLUGIN_SLUG=woocommerce-payments ../../bin/install-plugin.sh
