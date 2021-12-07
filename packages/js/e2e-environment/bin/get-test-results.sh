#!/usr/bin/env bash

RESULTS=$(node bin/parse-jest-results.js $1)

echo "$RESULTS"
