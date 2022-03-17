#!/usr/bin/env bash

#
# Script for generating Allure report with historical trend.
#

echo "Generating Allure report..."

# Copy contents of allure-report/history to allure-results/history to save previous results.
if [[ -d "allure-report/history" ]]; then
	echo "Previous report detected. Saving previous results..."

	# Create the allure-results/history directory if it doesn't exist yet.
	mkdir -p allure-results/history

	cp -r allure-report/history/* allure-results/history
fi

# Generate the report.
allure generate --clean
