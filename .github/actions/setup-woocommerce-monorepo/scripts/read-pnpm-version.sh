#!/usr/bin/env bash

PACKAGE_FILE=$1
if [[ -z "$PACKAGE_FILE" ]]; then
    echo "Usage: $0 <package.json>"
    exit 1
fi

awk -F'"' '/"pnpm": ".+"/{ print $4; exit; }' $PACKAGE_FILE
