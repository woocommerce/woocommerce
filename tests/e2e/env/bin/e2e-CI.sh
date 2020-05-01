#!/bin/bash
#
# Script for Travis CI

if [[ ${RUN_E2E} == 1 ]]; then
  npm install jest --global
  npm run docker:up
  npm run test:e2e
  npm run docker:down
fi
