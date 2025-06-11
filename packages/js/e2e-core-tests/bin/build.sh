#!/usr/bin/env bash
#
# Copy the WooCommerce sample data file to the package
#

PACKAGEPATH=$(dirname $(dirname "$0"))

cp -v $PACKAGEPATH/../../../plugins/woocommerce/sample-data/sample_products.csv $PACKAGEPATH/test-data
