#!/usr/bin/env bash

# Extract the relative path from the plugin root to this script directory.
# By doing so, we can run this script from anywhere.
script_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
head_dir=$(cd "$(dirname "$script_dir")" && cd ../../.. && pwd)
relative_path=${script_dir#$head_dir/}

db_dir="$script_dir/databases"

if [ -f "$db_dir/default.sql" ]; then
    # Import the initial database.
    # wp-env run tests-cli wp db import blocks_e2e_default.sql
    docker exec $(docker ps -aqf name=tests-cli) mysql -h tests-mysql -u root -ppassword tests-wordpress <"$db_dir/default.sql"
    printf "Default database imported successfully!\n\n"
    exit 0
else
    printf "Default database not found, creating a new one.\n\n"

    # Generate the child themes zip files before running the tests.
    # By doing so, we ensure that the zip files are up-to-date.
    themes_dir="$script_dir/themes"
    themes=(
        "storefront-child__block-notices-filter"
        "storefront-child__block-notices-template"
        "storefront-child__classic-notices-template"
        "twentytwentyfour-child__block-notices-filter"
        "twentytwentyfour-child__block-notices-template"
        "twentytwentyfour-child__classic-notices-template"
    )
    for theme in "${themes[@]}"; do
        # Define the path to the theme directory and the zip file.
        theme_dir="$themes_dir/$theme"
        zip_file="$themes_dir/$theme.zip"

        # Check if the zip file exists. If it does, delete it.
        if [ -f "$zip_file" ]; then
            echo "Deleting existing zip file for $theme."
            rm "$zip_file"
        fi

        # Navigate to the themes directory to ensure the zip contains only the theme folder name.
        # Then, create a fresh zip file.
        echo "Creating zip file for $theme."
        (cd "$themes_dir" && zip -r "$zip_file" "$theme" -x "*.git*" -x "*node_modules*")
    done

    # Run the main script in the container for better performance.
    wp-env run tests-cli -- bash wp-content/plugins/woocommerce/blocks-bin/playwright/scripts/index.sh

    # Export the initial database.
    # wp-env run tests-cli wp db export blocks_e2e_default.sql
    docker exec $(docker ps -aqf name=tests-cli) mysqldump -h tests-mysql -u root -ppassword tests-wordpress >"$db_dir/default.sql"
fi
