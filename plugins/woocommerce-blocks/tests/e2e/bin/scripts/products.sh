#!/usr/bin/env bash

###################################################################################################
# Import sample products and regenerate product lookup tables
###################################################################################################
wp import wp-content/plugins/woocommerce/sample-data/sample_products.xml --authors=skip
wp wc tool run regenerate_product_lookup_tables --user=1

# This is a hacky work around to fix product categories not having their parent category correctly assigned.
clothing_category_id=$(wp wc product_cat list --search="Clothing" --field=id --user=1)
tshirts_category_id=$(wp wc product_cat list --search="Tshirts" --field=id --user=1)
hoodies_category_id=$(wp wc product_cat list --search="Hoodies" --field=id --user=1)
wp wc product_cat update $tshirts_category_id --parent=$clothing_category_id --user=1
wp wc product_cat update $hoodies_category_id --parent=$clothing_category_id --user=1

# This is a hacky work around to fix product gallery images not being imported
# This sets up the product Hoodie to have product gallery images for e2e testing
hoodie_product_id=$(wp post list --post_type=product --field=ID --name="Hoodie" --format=ids)
image1=$(wp post list --post_type=attachment --field=ID --name="hoodie-with-logo-2.jpg" --format=ids)
image2=$(wp post list --post_type=attachment --field=ID --name="hoodie-green-1.jpg" --format=ids)
image3=$(wp post list --post_type=attachment --field=ID --name="hoodie-2.jpg" --format=ids)
wp post meta update $hoodie_product_id _product_image_gallery "$image1,$image2,$image3"

# Create a tag, so we can add tests for tag-related blocks and templates.
tag_id=$(wp wc product_tag create --name="Recommended" --slug="recommended" --description="Curated products selected by our experts" --porcelain --user=1)
wp wc product update $hoodie_product_id --tags="[ { \"id\": $tag_id } ]" --user=1

# This is a non-hacky work around to set up the cross sells product.
cap_product_id=$(wp post list --post_type=product --field=ID --name="Cap" --format=ids)
beanie_product_id=$(wp post list --post_type=product --field=ID --name="Beanie" --format=ids)
wp post meta update $beanie_product_id _crosssell_ids "$cap_product_id"

# Set a product out of stock
tshirt_with_logo_product_id=$(wp post list --post_type=product --field=ID --name="T-Shirt with Logo" --format=ids)
wp wc product update $tshirt_with_logo_product_id --in_stock=false --user=1

# Enable attribute archives.
attribute_ids=$(wp wc product_attribute list --fields=id --format=ids --user=1)
if [ -n "$attribute_ids" ]; then
  for id in $attribute_ids; do
    wp wc product_attribute update "$id" --has_archives=true --user=1
  done
else
  echo "No attribute IDs found."
fi
