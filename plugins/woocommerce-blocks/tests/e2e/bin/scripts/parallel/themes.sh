#!/usr/bin/env bash

# Get the directory of the current script.
script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# The theme dir is two levels up from where this script is running.
themes_dir="$script_dir/../../themes"

# Delete the child themes if they already exist.
wp theme delete storefront-child__block-notices
wp theme delete storefront-child__classic-notices
wp theme delete twentytwentyfour-child__block-notices
wp theme delete twentytwentyfour-child__classic-notices

# Install the child themes.
wp theme install "$themes_dir/storefront-child__block-notices.zip"
wp theme install "$themes_dir/storefront-child__classic-notices.zip"
wp theme install "$themes_dir/twentytwentyfour-child__block-notices.zip"
wp theme install "$themes_dir/twentytwentyfour-child__classic-notices.zip"
