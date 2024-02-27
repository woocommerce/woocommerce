#!/usr/bin/env bash

# Get the directory of the current script.
script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# The theme dir is two levels up from where this script is running.
themes_dir="$script_dir/../../themes"

# Delete the child themes if they already exist.
wp theme delete storefront-child
wp theme delete twentytwentyfour-child

# Install the child themes.
wp theme install "$themes_dir/storefront-child.zip"
wp theme install "$themes_dir/twentytwentyfour-child.zip"
