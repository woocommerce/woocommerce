#!/usr/bin/env bash

set -euo pipefail

package_dir="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
build_dir="$package_dir/build"
mirror="woocommerce/extension-changelogger"
mirror_dir="$build_dir/$mirror"

rm -rf -- "$build_dir"
mkdir -p -- "$mirror_dir"
printf '%s\n' "$mirror" > "$build_dir/mirrors.txt"

cd -- "$package_dir"
cp -R CHANGELOG.md LICENSE README.md composer.json composer.lock phpcs.xml src tests "$mirror_dir/"
