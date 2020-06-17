#!/bin/sh
npm run wp-env run tests-cli './wp-content/plugins/$(basename `pwd`)/bin/wp-env-config.sh'
