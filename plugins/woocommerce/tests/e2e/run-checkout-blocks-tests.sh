#!/bin/bash

# Load QIT environment
source "$(qit env:source)"

echo "Running Checkout Blocks Compatibility Tests"
echo "==========================================="
echo "Site URL: $QIT_SITE_URL"
echo ""

# Run the tests with detailed reporter
npx playwright test tests/checkout-blocks.spec.js --reporter=list