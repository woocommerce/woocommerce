#!/bin/bash

set -eo pipefail

PLUGIN_REPOSITORY='WordPress/gutenberg' PLUGIN_NAME=Gutenberg PLUGIN_SLUG=gutenberg ../../bin/install-plugin.sh
