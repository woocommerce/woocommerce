#!/usr/bin/env bash

if [ -f wp-content/database/.ht.sqlite.backup ]; then
	rm wp-content/database/.ht.sqlite
	cp wp-content/database/.ht.sqlite.backup wp-content/database/.ht.sqlite
else
	echo "Import file missing" >&2
	exit 1
fi
