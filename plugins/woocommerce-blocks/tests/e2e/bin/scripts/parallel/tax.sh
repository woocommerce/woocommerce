#!/usr/bin/env bash

wp option set woocommerce_calc_taxes yes

wp wc tax create \
    --user=1 \
    --rate=20 \
    --class=standard

wp wc tax create \
    --user=1 \
    --rate=10 \
    --class=reduced-rate

wp wc tax create \
    --user=1 \
    --rate=0 \
    --class=zero-rate
