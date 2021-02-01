#!/usr/bin/env bash

set -o errexit

npm run -s install-if-deps-outdated
npm run lint
npm run build
npm test

