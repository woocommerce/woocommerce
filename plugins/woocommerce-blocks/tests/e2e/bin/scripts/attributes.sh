#!/usr/bin/env bash

wp wc product_attribute create \
	--name=Color \
	--slug=pa_color \
	--user=1

wp wc product_attribute create \
	--name=Size \
	--slug=pa_size \
	--user=1
