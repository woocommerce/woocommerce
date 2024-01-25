#!/usr/bin/env bash

# Get the directory of the current script
script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Navigate two levels up and then to the themes directory
themes_dir="$script_dir/../../themes"

# Delete existing themes if they exist
wp theme delete storefront-child
wp theme delete twenty-twentyfour-child

# Install and activate the themes
wp theme install "$themes_dir/storefront-child.zip"
wp theme install "$themes_dir/twenty-twentyfour-child.zip"
