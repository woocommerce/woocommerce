#!/bin/bash

# Move to the project root by finding the directory containing plugins/woocommerce and pnpm-workspace.yaml
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

# Find all block.json files
find plugins/woocommerce/client/blocks/assets/js -name "block.json" | while read file; do
    # Read the block name from the JSON file
    block_name=$(cat "$file" | grep -o '"name": "[^"]*"' | cut -d'"' -f4 | cut -d'/' -f2)

    # Check if it's a parent block by looking for "parent" field
    if grep -q '"parent":' "$file"; then
        # It's an inner block
        mkdir -p "$TARGET_DIR/inner-blocks/$block_name"
        cp "$file" "$TARGET_DIR/inner-blocks/$block_name/block.json"
    else
        # It's a regular block
        mkdir -p "$TARGET_DIR/$block_name"
        cp "$file" "$TARGET_DIR/$block_name/block.json"
    fi
done