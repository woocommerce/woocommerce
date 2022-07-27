#!/bin/bash
if [ -z "${@:2}" ]
then
    pnpm -w run --filter=$npm_package_name "$1"
else
    pnpm -w run --filter=$npm_package_name "$1" -- -- ${@:2}
fi
