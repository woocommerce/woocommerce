#!/bin/bash
# ------------------------------------------------------------------
# Package Setup - Run before this test package's tests
# ------------------------------------------------------------------
# This script creates test data specific to this test package
# It runs after global setup and before the tests

set -euo pipefail

echo "[setup] Starting Checkout Blocks test setup..."

# Configure WooCommerce settings first
echo "[setup] Configuring WooCommerce..."
php bootstrap/configure-woocommerce.php

# Create necessary pages
echo "[setup] Creating test pages..."
php bootstrap/create-pages.php

echo "[setup] Creating test products for E2E tests..."

# Create a simple test product for checkout tests
PRODUCT_ID=$(wp wc product create \
    --name="Test Product" \
    --type="simple" \
    --regular_price="10.00" \
    --description="Test product for E2E checkout tests" \
    --short_description="Test product" \
    --manage_stock=false \
    --in_stock=true \
    --user=1 \
    --porcelain)

echo "[setup] Test product created (ID: $PRODUCT_ID)"

# Create a few more products for variety
wp wc product create \
    --name="Another Product" \
    --type="simple" \
    --regular_price="25.00" \
    --description="Another test product" \
    --manage_stock=false \
    --in_stock=true \
    --user=1 \
    --porcelain

wp wc product create \
    --name="Premium Product" \
    --type="simple" \
    --regular_price="99.99" \
    --description="Premium test product" \
    --manage_stock=false \
    --in_stock=true \
    --user=1 \
    --porcelain

# Create a variable product for complex tests
VARIABLE_ID=$(wp wc product create \
    --name="Variable Product" \
    --type="variable" \
    --description="Variable product for testing" \
    --user=1 \
    --porcelain)

# Create product attributes
wp wc product_attribute create \
    --name="Size" \
    --slug="size" \
    --type="select" \
    --order_by="menu_order" \
    --has_archives=false \
    --porcelain || echo "[setup] Size attribute already exists"

# Add variations
wp wc product_variation create $VARIABLE_ID \
    --regular_price="15.00" \
    --attributes='[{"name":"Size","option":"Small"}]' \
    --user=1 \
    --porcelain

wp wc product_variation create $VARIABLE_ID \
    --regular_price="20.00" \
    --attributes='[{"name":"Size","option":"Large"}]' \
    --user=1 \
    --porcelain

echo "[setup] Created 4 test products including variable product"

# Create test coupons
wp wc shop_coupon create \
    --code="TESTCOUPON" \
    --discount_type="percent" \
    --amount="10" \
    --user=1 \
    --porcelain

wp wc shop_coupon create \
    --code="FIXED5" \
    --discount_type="fixed_cart" \
    --amount="5" \
    --user=1 \
    --porcelain

echo "[setup] Created test coupons: TESTCOUPON (10% off), FIXED5 ($5 off)"

# Create test customer account
wp user create customer customer@example.com --role=customer --user_pass=password --first_name=John --last_name=Doe --display_name="John Doe" || echo "[setup] Customer account already exists"

# Set customer billing/shipping addresses
wp user meta update customer billing_first_name "John"
wp user meta update customer billing_last_name "Doe"
wp user meta update customer billing_address_1 "123 Main St"
wp user meta update customer billing_city "San Francisco"
wp user meta update customer billing_state "CA"
wp user meta update customer billing_postcode "94107"
wp user meta update customer billing_country "US"
wp user meta update customer billing_email "customer@example.com"
wp user meta update customer billing_phone "555-1234"

wp user meta update customer shipping_first_name "John"
wp user meta update customer shipping_last_name "Doe"
wp user meta update customer shipping_address_1 "123 Main St"
wp user meta update customer shipping_city "San Francisco"
wp user meta update customer shipping_state "CA"
wp user meta update customer shipping_postcode "94107"
wp user meta update customer shipping_country "US"

echo "[setup] Created test customer account with addresses"

echo "[setup] Package setup complete"