#!/bin/bash

PR_PATTERNS=$(echo $1 | sed 's/[^,]*/\(#&)/g' | sed 's/,/\\|/g')
git log --pretty=format:'%ad,%s' --all --date=iso-local --author-date-order --reverse --grep "$PR_PATTERNS" | sort -u | grep -o '\(#[0-9]\+\)'

