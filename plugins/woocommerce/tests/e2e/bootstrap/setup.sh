#!/bin/bash
set -e

echo "[setup] WooCommerce E2E test environment setup..."

# Note: Tests create their own test data as needed to ensure isolation
# This follows the e2e-pw pattern where each test manages its own data

echo "[setup] Setup complete."