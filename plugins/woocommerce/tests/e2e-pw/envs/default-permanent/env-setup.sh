#!/bin/bash

set -eo pipefail

echo "Default permanent site setup."

SCRIPT_PATH=$(
  cd "$(dirname "${BASH_SOURCE[0]}")" || return
  pwd -P
)

"$SCRIPT_PATH"/../validate-required-variables.sh
