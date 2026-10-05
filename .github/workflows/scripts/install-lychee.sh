#!/usr/bin/env bash

# Installs a checksum-pinned lychee release for GitHub-hosted x86_64 Linux runners
# and adds it to the job PATH. Bump both values together when upgrading.

set -euo pipefail

LYCHEE_VERSION='0.24.2'
LYCHEE_SHA256='1f4e0ef7f6554a6ed33dd7ac144fb2e1bbed98598e7af973042fc5cd43951c9a'

target='x86_64-unknown-linux-gnu'
archive="lychee-${target}.tar.gz"
download_dir="$( mktemp -d )"

curl --silent --show-error --fail --location --retry 3 \
	--output "${download_dir}/${archive}" \
	"https://github.com/lycheeverse/lychee/releases/download/lychee-v${LYCHEE_VERSION}/${archive}"
printf '%s  %s\n' "${LYCHEE_SHA256}" "${download_dir}/${archive}" | sha256sum --check --strict --quiet -

tar -xzf "${download_dir}/${archive}" -C "${download_dir}" "lychee-${target}/lychee"
install -D "${download_dir}/lychee-${target}/lychee" "${RUNNER_TEMP}/lychee/bin/lychee"
rm -rf "${download_dir}"

printf '%s\n' "${RUNNER_TEMP}/lychee/bin" >> "${GITHUB_PATH}"
"${RUNNER_TEMP}/lychee/bin/lychee" --version
