#!/usr/bin/env bash
set -euo pipefail

WP="wp-env --config .wp-env.mcp.json run cli wp"

$WP user get customer > /dev/null 2>&1 || $WP user create customer customer@example.com --role=customer
