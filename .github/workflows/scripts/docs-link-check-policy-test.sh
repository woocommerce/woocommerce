#!/usr/bin/env bash

# Proves the pull-request docs link-check policy without network access.
# `lychee --dump` applies the same URL filters as a real run and prints only
# the URLs it would request, so diffing it against the expected list shows
# exactly which hosts a docs change can make the CI runner contact.
#
# Usage: bash .github/workflows/scripts/docs-link-check-policy-test.sh
# Set LYCHEE to a binary path when lychee is not on PATH.

set -euo pipefail

policy_dir="$( cd -- "$( dirname -- "${BASH_SOURCE[0]}" )/../docs-link-check" && pwd )"
lychee="${LYCHEE:-lychee}"

diff -u "${policy_dir}/policy-expected.txt" <(
	"${lychee}" --config "${policy_dir}/pr.toml" --dump --root-dir "${policy_dir}" "${policy_dir}/policy-fixture.md" | LC_ALL=C sort
)

printf 'PASS: docs link-check policy\n'
