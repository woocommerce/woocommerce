pnpm -w exec turbo run $1 --output-logs=new-only --filter=$npm_package_name -- -- "${@:2}"
