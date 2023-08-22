#!/usr/bin/env bash

script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Empty the site to remove unused pages and posts created by WP and Woo.
wp site empty --yes

# Attributes must be created before importing products.
bash $script_dir/attributes.sh

# Run all scripts in parallel at maximum 10 at a time
find $script_dir/parallel/*.sh -maxdepth 1 -type f | xargs -P10 -n1 bash

# Run rewrite script last to ensure all posts are created before running it
bash $script_dir/rewrite.sh
