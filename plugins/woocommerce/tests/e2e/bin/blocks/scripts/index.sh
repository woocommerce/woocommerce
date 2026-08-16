#!/usr/bin/env bash

# This script deliberately does not `set -e`. Some steps below are not
# idempotent: attributes.sh exits non-zero once the attributes it creates
# already exist, which is every re-seed of an existing environment, since
# `wp site empty` leaves attribute taxonomies in place. Under `set -e` the
# seed would die there, before any product is imported. Guard steps
# individually until those steps are made re-runnable.

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
# Guarded because test-env-setup.sh snapshots the database once this returns, so
# a half-seeded site would be restored before every test in the suite.
bash "$script_dir/product-collection-sort-data.sh" || exit 1

# Run rewrite script last to ensure all posts are created before running it.
bash "$script_dir/rewrite.sh"
