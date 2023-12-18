#!/usr/bin/env bash

###################################################################################################
# Import sample products and regenerate product lookup tables
###################################################################################################
wp import wp-content/plugins/woocommerce/sample-data/sample_products.xml --authors=skip
wp wc tool run regenerate_product_lookup_tables --user=1

# This is a hacky work around to fix product gallery images not being imported
# This sets up the product Hoodie to have product gallery images for e2e testing
post_id=$(wp post list --post_type=product --field=ID --name="Hoodie" --format=ids)
image1=$(wp post list --post_type=attachment --field=ID --name="hoodie-with-logo-2.jpg" --format=ids)
image2=$(wp post list --post_type=attachment --field=ID --name="hoodie-green-1.jpg" --format=ids)
image3=$(wp post list --post_type=attachment --field=ID --name="hoodie-2.jpg" --format=ids)
wp post meta update $post_id _product_image_gallery "$image1,$image2,$image3"

# Add some reviews to products
product1_id=$(wp post list --post_type=product --field=ID --name="V Neck T Shirt" --format=ids)
product2_id=$(wp post list --post_type=product --field=ID --name="Hoodie" --format=ids)
product3_id=$(wp post list --post_type=product --field=ID --name="Hoodie with Logo" --format=ids)
product4_id=$(wp post list --post_type=product --field=ID --name="T-Shirt" --format=ids)
product5_id=$(wp post list --post_type=product --field=ID --name="Beanie" --format=ids)
wp wc product_review create $product1_id --user=1 --review="Great V Neck T Shirt!" --rating="5" --name="John Doe" --email="john.doe@example.com"
wp wc product_review create $product2_id --user=1 --review="Nice Hoodie!" --rating="4" --name="John Doe" --email="john.doe@example.com"
wp wc product_review create $product3_id --user=1 --review="Not bad Hoodie with Logo!" --rating="3" --name="John Doe" --email="john.doe@example.com"
wp wc product_review create $product4_id --user=1 --review="Mediocre T-Shirt!" --rating="2" --name="John Doe" --email="john.doe@example.com"
wp wc product_review create $product5_id --user=1 --review="Meh Beanie!" --rating="1" --name="John Doe" --email="john.doe@example.com"
