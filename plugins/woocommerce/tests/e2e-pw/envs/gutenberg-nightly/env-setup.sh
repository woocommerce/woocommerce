#!/bin/bash

set -eo pipefail

SCRIPT_PATH=$(
  cd "$(dirname "${BASH_SOURCE[0]}")" || return
  pwd -P
)

PLUGIN_REPOSITORY='bph/gutenberg' PLUGIN_NAME=Gutenberg PLUGIN_SLUG=gutenberg "$SCRIPT_PATH"/../../bin/install-plugin.sh
