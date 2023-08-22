#!/usr/bin/env bash

###################################################################################################
# Import sample products and regenerate product lookup tables
###################################################################################################
wp import wp-content/plugins/woocommerce/sample-data/sample_products.xml --authors=skip
wp wc tool run regenerate_product_lookup_tables --user=1
