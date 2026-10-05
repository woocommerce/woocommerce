#!/usr/bin/env bash

# The Playwright global setup (tests/e2e/fixtures/blocks-setup.ts) exports the
# database once this seed has run, and every test restores that snapshot before
# it starts. So a step that fails silently here does not fail one spec, it hands
# the whole suite a subtly wrong environment. Every step must therefore be
# re-runnable, so that a re-seed of an existing environment is not mistaken for
# a real failure.
set -euo pipefail

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Empty the site to remove unused pages and posts created by WP and Woo.
wp site empty --yes

# Attributes must be created before importing products.
bash "$script_dir/attributes.sh"

# Products must be created before anything else so the ids are deterministic.
bash "$script_dir/products.sh"

# Run all scripts in parallel at maximum 10 at a time.
#
# `xargs` reports any child failure as its own exit 123, so it names neither the
# script that failed nor the code it failed with — and with ten of them writing
# to the same log at once, the output alone does not identify it either. Wrap
# each step so a failure announces itself before the seed dies.
find "$script_dir"/parallel/*.sh -maxdepth 1 -type f | xargs -P10 -n1 bash -c \
	'bash "$0" || { status=$?; echo "Seed step failed: $0 (exit $status)" >&2; exit "$status"; }'

# Add deterministic ratings and sales data for product collection sorting.
bash "$script_dir/product-collection-sort-data.sh"

# Run rewrite script last to ensure all posts are created before running it.
bash "$script_dir/rewrite.sh"
