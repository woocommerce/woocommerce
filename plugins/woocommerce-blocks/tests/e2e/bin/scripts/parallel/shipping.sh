#!/usr/bin/env bash

wp wc shipping_zone_method create 0 \
	--order=1 \
	--enabled=true \
	--user=1 \
	--settings='{"title":"Flat rate shipping", "cost": "10"}' \
	--method_id=flat_rate

wp wc shipping_zone_method create 0 \
	--order=2 \
	--enabled=true \
	--user=1 \
	--settings='{"title":"Free shipping"}' \
	--method_id=free_shipping
