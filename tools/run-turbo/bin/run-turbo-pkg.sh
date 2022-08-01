#!/bin/bash
node ./node_modules/run-turbo/bin/process-args.js "${@:1}" "--turbo-filter=$npm_package_name"
