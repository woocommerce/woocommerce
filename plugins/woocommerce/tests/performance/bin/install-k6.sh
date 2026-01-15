#!/bin/bash

set -eo pipefail

# To update k6, change the version and checksums below.
# Get checksums from: https://github.com/grafana/k6/releases/download/v${VERSION}/k6-v${VERSION}-checksums.txt
K6_VERSION="1.4.2"
K6_CHECKSUMS="
linux-amd64.tar.gz  c827501f510265db9c3313e4164d2585a97c7a3485ed440b93f7b1cfe6facd28
linux-arm64.tar.gz  0e9515502f80edb562f12c0b12a59085c0b113c6416d728e3c2a9e5a7506cc5c
macos-amd64.zip     ed79f1356fcc98ac645e6c9732def8fcd84cfeec8c6a2e92476663bda27b9550
macos-arm64.zip     93635cccab3f7c689f890218ccbb92b0440a42f1380681a7d732a072915d7b76
"

DOWNLOAD_URL="https://github.com/grafana/k6/releases/download/v$K6_VERSION"
SCRIPT_PATH=$(cd "$(dirname "${BASH_SOURCE[0]}")" || return; pwd -P)

get_checksum() {
  local platform=$1
  echo "$K6_CHECKSUMS" | grep "^$platform" | awk '{print $2}'
}

verify_checksum() {
  local file=$1
  local expected_checksum=$2

  echo "Verifying checksum for $file..."

  if command -v sha256sum &> /dev/null; then
    actual_checksum=$(sha256sum "$file" | awk '{print $1}')
  elif command -v shasum &> /dev/null; then
    actual_checksum=$(shasum -a 256 "$file" | awk '{print $1}')
  else
    echo "Warning: No SHA256 tool found. Skipping checksum verification."
    return 0
  fi

  if [ "$actual_checksum" != "$expected_checksum" ]; then
    echo "Checksum verification failed!"
    echo "Expected: $expected_checksum"
    echo "Actual:   $actual_checksum"
    rm -f "$file"
    exit 1
  fi

  echo "Checksum verified successfully."
}

download_archive() {
  local platform=$1
  local archive="k6-v$K6_VERSION-$platform"
  local download_url="$DOWNLOAD_URL/$archive"
  local download_path="$SCRIPT_PATH/$archive"

  echo "Downloading from $download_url to $download_path"
  curl "$download_url" -L -o "$download_path"

  # Verify checksum
  local expected_checksum
  expected_checksum=$(get_checksum "$platform")
  if [ -n "$expected_checksum" ]; then
    verify_checksum "$download_path" "$expected_checksum"
  else
    echo "Warning: No checksum available for $platform"
  fi
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
    platform="macos-$arch.zip"
    download_archive "$platform"
    unzip -j -o "$SCRIPT_PATH/k6-v$K6_VERSION-$platform" -d "$SCRIPT_PATH"
elif [ "$(expr substr $(uname -s) 1 5)" == "Linux" ]; then
    platform="linux-$arch.tar.gz"
    download_archive "$platform"
    tar --strip-components=1 -xzf "$SCRIPT_PATH/k6-v$K6_VERSION-$platform" -C "$SCRIPT_PATH"
else
    echo "Unsupported operating system. Please check K6 docs and install the right version for your system."
    exit 1
fi

"$SCRIPT_PATH/k6" version
