#!/bin/sh
BASENAME=$(basename "`pwd`")
npm run wp-env run tests-cli './wp-content/plugins/'$BASENAME'/bin/wp-env-config.sh'
