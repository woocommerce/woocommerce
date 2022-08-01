#!/bin/bash
echo "hello world!"
echo "${@:2}"

filter="*"

for arg in "$@"; do # transform long options to short ones  
  case "$arg" in
      "--t-filter") filter="$arg";
  esac
done

pnpm -- turbo run "$1" --filter=$filter -- -- ${@:2}
