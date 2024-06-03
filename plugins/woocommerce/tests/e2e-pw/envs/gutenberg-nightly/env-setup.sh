#!/bin/bash

set -eo pipefail

PLUGIN_REPOSITORY='bph/gutenberg' PLUGIN_NAME=Gutenberg PLUGIN_SLUG=gutenberg ../../bin/install-plugin.sh
