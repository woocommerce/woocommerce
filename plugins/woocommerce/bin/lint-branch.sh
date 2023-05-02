#!/bin/sh

baseBranch=${1:-"trunk"}

chg=$(git diff $(git merge-base HEAD $baseBranch) --relative --name-only -- '*.php')

[ -z $chg ] || composer exec phpcs-changed -- -s --git --git-base $baseBranch $chg
