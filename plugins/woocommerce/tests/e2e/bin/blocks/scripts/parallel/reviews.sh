#!/usr/bin/env bash

# Add reviews to Hoodie. Order matters: specs treat the last created review as most recent.
# Include three distinct ratings so latest / highest / lowest are different reviews.
hoodie_post_id=$(wp post list --post_type=product --field=ID --name="Hoodie" --format=ids)
wp wc product_review create $hoodie_post_id --name="Jane Smith" --email="customer@woocommerceblockse2etestsuite.com" --review="It's ok." --rating=3 --user=1
wp wc product_review create $hoodie_post_id --name="Jane Smith" --email="customer@woocommerceblockse2etestsuite.com" --review="Nice album!" --rating=5 --user=1
wp wc product_review create $hoodie_post_id --name="Jane Smith" --email="customer@woocommerceblockse2etestsuite.com" --review="Not bad." --rating=4 --user=1

cap_post_id=$(wp post list --post_type=product --field=ID --name="Cap" --format=ids)
wp wc product_review create $cap_post_id --name="Jane Smith" --email="customer@woocommerceblockse2etestsuite.com" --review="Really awful." --rating=1 --user=1
wp wc product_review create $cap_post_id --name="Jane Smith" --email="customer@woocommerceblockse2etestsuite.com" --review="Bad!" --rating=2 --user=1

