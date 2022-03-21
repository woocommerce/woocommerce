#!/usr/bin/env bash

set -o errexit

pnpm run -s install-if-deps-outdated
pnpm run lint
pnpm run build
pnpm test

