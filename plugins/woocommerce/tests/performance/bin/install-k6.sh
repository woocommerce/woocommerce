#!/bin/bash

set -eo pipefail

K6_VERSION="0.53.0"
DOWNLOAD_URL="https://github.com/grafana/k6/releases/download/v$K6_VERSION"
SCRIPT_PATH=$(cd "$(dirname "${BASH_SOURCE[0]}")" || return; pwd -P)

download_archive() {
  local archive=$1
  local download_url="$DOWNLOAD_URL/$archive"
  local download_path="$SCRIPT_PATH/$archive"

  echo "Downloading from $download_url to $download_path"
  curl "$download_url" -L -o "$download_path"
}

arch=$(uname -m)

if [[ "$arch" == "x86_64" || "$arch" == "amd64" ]]; then
    arch="amd64"
  elif [[ "$arch" == "aarch64" || "$arch" == "arm64" ]]; then
    arch="arm64"
  else
    echo "Unsupported CPU architecture: $arch. Please check K6 docs and install the right version for your system."
    exit 1
fi

if [ "$(uname)" == "Darwin" ]; then
	archive="k6-v$K6_VERSION-macos-$arch.zip"
    download_archive "$archive"
    unzip -j -o "$SCRIPT_PATH/$archive" -d "$SCRIPT_PATH"
elif [ "$(expr substr $(uname -s) 1 5)" == "Linux" ]; then
	archive="k6-v$K6_VERSION-linux-$arch.tar.gz"
    download_archive "$archive"
    tar --strip-components=1 -xzf "$SCRIPT_PATH/$archive" -C "$SCRIPT_PATH"
else
    echo "Unsupported operating system. Please check K6 docs and install the right version for your system."
    exit 1
fi

"$SCRIPT_PATH/k6" version
