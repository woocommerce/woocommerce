#!/usr/bin/env bash

# test-env-setup.sh snapshots the database once this returns and restores it
# before every test, so a step that fails silently here poisons the whole suite
# rather than failing one spec. Every step must therefore be re-runnable, so
# that a re-seed of an existing environment is not mistaken for a real failure.
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Empty the site to remove unused pages and posts created by WP and Woo.
wp site empty --yes

# Attributes must be created before importing products.
bash "$script_dir/attributes.sh"

# Products must be created before anything else so the ids are deterministic.
bash "$script_dir/products.sh"

# Run all scripts in parallel at maximum 10 at a time.
find "$script_dir"/parallel/*.sh -maxdepth 1 -type f | xargs -P10 -n1 bash

# Add deterministic ratings and sales data for product collection sorting.
bash "$script_dir/product-collection-sort-data.sh"

# Run rewrite script last to ensure all posts are created before running it.
bash "$script_dir/rewrite.sh"
