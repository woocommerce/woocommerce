#!/bin/bash

set -eo pipefail

echo "Default permanent site setup."

SCRIPT_PATH=$(
  cd "$(dirname "${BASH_SOURCE[0]}")" || return
  pwd -P
)

dotenvPath="$SCRIPT_PATH"/../../.env
echo "Decrypting .env.enc file to $dotenvPath"

openssl enc -aes-256-cbc -iter 1000 -d -pass env:E2E_ENV_KEY -in "$SCRIPT_PATH"/.env.enc -out "$dotenvPath"
