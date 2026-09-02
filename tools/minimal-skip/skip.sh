#!/usr/bin/env bash
#
# Run one test locally, and tell CI it can skip it.
#
# A commit status can be attached to a commit GitHub has never seen, before the
# branch that would trigger CI is pushed. That ordering is the whole idea:
# publishing after the push is too late, because CI has already started.
#
#   ./tools/minimal-skip/skip.sh
#
# Requires the GitHub CLI, authenticated.

set -euo pipefail

REPO='woocommerce/woocommerce'
PACKAGE='@woocommerce/number'
CONTEXT='local-skip/@woocommerce/number'

SHA=$(git rev-parse HEAD)
REF="refs/local-skip/$SHA"

# Whatever happens, do not leave the temporary ref on the remote.
trap 'git push --no-verify -q origin --delete "$REF" 2>/dev/null || true' EXIT

echo "1. Run the test locally"
pnpm --filter="$PACKAGE" test:js

echo
echo "2. Make this commit known to GitHub without pushing the branch"
# refs/local-skip/* is outside refs/heads/* and refs/tags/*, so no workflow can
# trigger on it — but the commit becomes addressable, which a status requires.
git push --no-verify -q origin "HEAD:$REF"

echo
echo "3. Publish the receipt"
gh api -X POST "repos/$REPO/statuses/$SHA" \
  -f state=success \
  -f context="$CONTEXT" \
  -f description='passed locally' \
  --jq '"   " + .context + " -> " + .state'

echo
echo "Done. Push the branch and CI will skip $PACKAGE."
