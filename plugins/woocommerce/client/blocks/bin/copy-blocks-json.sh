#!/bin/bash

# This script mimics the CopyWebpackPlugin behavior from the WooCommerce Blocks webpack configuration
# to make block.json files available for unit testing without requiring a full build.
# Ensure that the logic of this script is kept in sync with the logic of the CopyWebpackPlugin in the WooCommerce Blocks webpack configuration:
# https://github.com/woocommerce/woocommerce/blob/84d1da7be3cbd3d8f40b17ad58729f668fd82b6a/plugins/woocommerce/client/blocks/bin/webpack-configs.js#L229-L256

# Move to the project root
while [ ! -d "plugins/woocommerce" ] || [ ! -f "pnpm-workspace.yaml" ]; do
    if [ "$PWD" = "/" ]; then
        echo "Error: Could not find project root"
        exit 1
    fi
    cd ..
done

# Set target directory
TARGET_DIR="plugins/woocommerce/assets/client/blocks"

# Create target directory if it doesn't exist
mkdir -p "$TARGET_DIR"

# Define generic blocks as a space-separated string (keep in sync with webpack-entries.js)
generic_blocks="accordion-group accordion-header accordion-item accordion-panel"

# Find all block.json files
find plugins/woocommerce/client/blocks/assets/js -name "block.json" | while read file; do
    # Read the block name from the JSON file
    block_name=$(cat "$file" | grep -o '"name": "[^"]*"' | cut -d'"' -f4 | cut -d'/' -f2)

    # Function to check if a block is in the generic_blocks string
    is_generic_block=false
    for gb in $generic_blocks; do
        if [ "$block_name" = "$gb" ]; then
            is_generic_block=true
            break
        fi
    done

    # Check if it's a parent block by looking for "parent" field, but treat as regular if generic
    if grep -q '"parent":' "$file" && [ "$is_generic_block" = false ]; then
        # It's an inner block
        target_path="$TARGET_DIR/inner-blocks/$block_name/block.json"
        mkdir -p "$TARGET_DIR/inner-blocks/$block_name"
        if [ ! -f "$target_path" ]; then
            cp "$file" "$target_path"
        fi
    else
        # It's a regular block
        target_path="$TARGET_DIR/$block_name/block.json"
        mkdir -p "$TARGET_DIR/$block_name"
        if [ ! -f "$target_path" ]; then
            cp "$file" "$target_path"
        fi
    fi
done
