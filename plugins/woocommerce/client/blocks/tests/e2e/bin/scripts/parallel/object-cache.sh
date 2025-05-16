#!/usr/bin/env bash

rm -f wp-content/*.sqlite
wp plugin install sqlite-object-cache --activate
