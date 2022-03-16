#!/usr/bin/env bash

#
# Script for generating Allure reports.
#

echo "Generating Allure report..."

# Prepare the allure-results/history directory
mkdir -p allure-results/history

# Copy contents of allure-report/history to allure-results/history to preserve historical trend
if [[ -d "allure-report/history" ]]; then
	echo "Previous report detected. Saving previous results for historical analysis..."
	cp -r allure-report/history/* allure-results/history
fi

# Generate the report
allure generate --clean
