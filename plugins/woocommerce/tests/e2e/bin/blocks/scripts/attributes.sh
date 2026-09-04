#!/usr/bin/env bash

set -euo pipefail

# `wp site empty` does not remove product attributes: they live in the
# woocommerce_attribute_taxonomies table rather than being terms, so they
# survive it. Creating them again on a re-seed fails with
# `Slug "color" is already in use`, which is why this script only creates the
# attributes that are missing.
existing_slugs="$(wp wc product_attribute list --field=slug --user=1)"

if ! printf '%s\n' "$existing_slugs" | grep -qx 'pa_color'; then
	wp wc product_attribute create \
		--name=Color \
		--slug=pa_color \
		--user=1
fi

if ! printf '%s\n' "$existing_slugs" | grep -qx 'pa_size'; then
	wp wc product_attribute create \
		--name=Size \
		--slug=pa_size \
		--user=1
fi
