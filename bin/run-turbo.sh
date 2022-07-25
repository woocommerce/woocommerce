pnpm -C "$BASH_SOURCE/../../" -- turbo run $1 --filter=$npm_package_name "${@:2}"
