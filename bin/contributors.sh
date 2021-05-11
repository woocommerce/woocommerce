#!/bin/bash

read -p 'What date (YYYY-MM-DD) should we get contributions since? (i.e. date of previous release): ' from_date
read -sp 'Provide a personal access token (you must): ' auth_token

ignored_users="renovate-bot,apps/renovate,renovate,renovate[bot],github-actions[bot],dependabot,dependabot[bot]"
output_file="contributors.html"
common_arguments="--owner woocommerce --fromDate $from_date --authToken $auth_token --cols 6 --sortBy contributions --format html --sortOrder desc --showlogin true --filter $ignored_users"

echo ""

echo "<h2>WooCommerce core</h2>" > $output_file
echo "Generating contributor list for WC core since $from_date"
./node_modules/.bin/githubcontrib --repo woocommerce $common_arguments  >> $output_file

echo "<h2>WooCommerce Admin</h2>" >> $output_file
echo "Generating contributor list for WC Admin since $from_date"
./node_modules/.bin/githubcontrib --repo woocommerce-admin $common_arguments >> $output_file

echo "<h2>WooCommerce Blocks</h2>" >> $output_file
echo "Generating contributor list for WC Blocks since $from_date"
./node_modules/.bin/githubcontrib --repo woocommerce-gutenberg-products-block $common_arguments >> $output_file

echo "<h2>Action Scheduler</h2>" >> $output_file
echo "Generating contributor list for Action Scheduler since $from_date"
./node_modules/.bin/githubcontrib --repo action-scheduler $common_arguments >> $output_file

echo "Output generated to $output_file."
