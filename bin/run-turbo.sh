pnpm -C "$BASH_SOURCE/../../" exec turbo run --filter=$npm_package_name "$1" -- -- ${@:2}
