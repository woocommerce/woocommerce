#!/usr/bin/env bash

set -euo pipefail

wp wc shop_coupon create --code=TESTCOUPON --amount=10 --discount_type=fixed_cart --user=1
