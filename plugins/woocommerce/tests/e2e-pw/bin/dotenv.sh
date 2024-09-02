#!/bin/bash

set -eo pipefail

operation=""

while getopts "ed" opt; do
  case $opt in
    e)
      operation="encrypt"
      ;;
    d)
      operation="decrypt"
      ;;
    \?)
      echo "Invalid option: -$OPTARG" >&2
      exit 1
      ;;
  esac
done

shift $((OPTIND-1))

if [ -z "$1" ]; then
  echo "ERROR: Missing environment name argument"
  echo "Usage: $0 [-e|-d] env-name"
  exit 1
fi

SCRIPT_PATH=$(cd "$(dirname "${BASH_SOURCE[0]}")" || return; pwd -P)
encFile="$SCRIPT_PATH/../envs/$1/.env.enc"
decFile="$SCRIPT_PATH/../.env"

if [ "$operation" == "encrypt" ]; then
  openssl enc -aes-256-cbc -iter 1000 -pass env:E2E_ENV_KEY -in "$decFile" -out "$encFile"
elif [ "$operation" == "decrypt" ]; then
  openssl enc -aes-256-cbc -iter 1000 -d -pass env:E2E_ENV_KEY -in "$encFile" -out "$decFile"
else
  echo "ERROR: No operation specified"
  echo "Usage: $0 [-e|-d] path/to/env"
  exit 1
fi
