#!/usr/bin/env bash
#
# Proof of concept for local-first CI receipts.
#
# Demonstrates the one part of the design that is not obvious: a commit status
# can be attached to a commit GitHub has never seen, before the branch that
# would trigger CI is pushed. That removes the race between publishing a result
# and CI starting.
#
# Runs against the real repository. Creates one ref and one commit status, then
# removes the ref. Safe to run repeatedly.
#
set -uo pipefail

REPO=woocommerce/woocommerce
CHECK_PKG=@woocommerce/number          # real check: 17 jest tests, ~1.5s, no Docker
CONTEXT="local-ci/v1/@woocommerce/number::JavaScript"   # projectName-qualified: 21 packages share the name "JavaScript"

# An interrupted run must not leave the temporary ref behind.
TEMP_REF=""
cleanup() {
  [ -n "$TEMP_REF" ] && git push --no-verify -q origin --delete "$TEMP_REF" 2>/dev/null
}
trap cleanup EXIT INT TERM

say() { printf '\n\033[1m%s\033[0m\n' "$*"; }
ok()  { printf '  \033[32m✓\033[0m %s\n' "$*"; }
no()  { printf '  \033[31m✗\033[0m %s\n' "$*"; }

# ---------------------------------------------------------------- 1. token
say "1 · Resolve a token from what is already on the machine"
resolve_token() {
  [ -n "${GH_TOKEN:-}" ]     && { printf '%s' "$GH_TOKEN"; return; }
  [ -n "${GITHUB_TOKEN:-}" ] && { printf '%s' "$GITHUB_TOKEN"; return; }
  command -v gh >/dev/null 2>&1 && gh auth token 2>/dev/null && return
  GIT_TERMINAL_PROMPT=0 printf 'protocol=https\nhost=github.com\n\n' \
    | git credential fill 2>/dev/null | sed -n 's/^password=//p'
}
TOKEN=$(resolve_token)
[ -n "$TOKEN" ] && ok "found one (never prompts; no token means publish nothing and exit 0)" \
                || { no "no token — a real run would still execute the checks, then exit 0"; exit 0; }

# ---------------------------------------------------------- 2. one planner
say "2 · Ask the planner what CI would run for this diff"
echo "  the same ci-jobs tool CI uses, run locally:"
PLAN=$(pnpm utils ci-jobs --base-ref trunk 2>/dev/null | grep -E '^-  ' | head -10)
if [ -n "$PLAN" ]; then
  printf '%s\n' "$PLAN" | sed 's/^/    /'
else
  echo "    (no jobs for this diff — it touches no project CI cares about)"
fi
echo "    the design reads this list; this POC substitutes one job below"

# --------------------------------------------------------- 3. run a check
say "3 · Run an eligible check locally"
START=$(date +%s)
if pnpm --filter="$CHECK_PKG" test:js >/tmp/poc-check.log 2>&1; then
  ok "$CHECK_PKG passed in $(( $(date +%s) - START ))s — $(grep -oE 'Tests: +[0-9]+ passed' /tmp/poc-check.log | head -1)"
  echo "    this is the same command CI runs for that package's JavaScript job"
else
  no "check failed — a real run would stop here and not push"; exit 1
fi

# ------------------------------------------- 4. make the SHA known to GitHub
say "4 · Publish the commit to a ref that triggers nothing"
SHA=$(git rev-parse HEAD)
echo "  before: does GitHub know this commit?"
BEFORE=$(curl -s -o /dev/null -w '%{http_code}' -H "Authorization: Bearer $TOKEN" \
  "https://api.github.com/repos/$REPO/commits/$SHA")
echo "    GET /commits/$SHA → HTTP $BEFORE"
if [ "$BEFORE" = "200" ]; then
  echo "    NOTE: this commit is already on the remote (pushed, or open in a PR), so the"
  echo "          422 -> 200 transition cannot be shown. To see it, make a local commit"
  echo "          and run this before pushing. Everything below still runs for real."
fi

# Count workflow runs BEFORE the temp push, so step 5 measures what the push caused
RUNS_BEFORE=$(curl -s -H "Authorization: Bearer $TOKEN" \
  "https://api.github.com/repos/$REPO/actions/runs?head_sha=$SHA" \
  | python3 -c 'import json,sys; print(json.load(sys.stdin)["total_count"])')
TEMP_REF="refs/local-ci/$SHA"
git push --no-verify -q origin "HEAD:$TEMP_REF" 2>/dev/null && ok "pushed to $TEMP_REF"
echo "  after:"
KNOWN=$(curl -s -o /dev/null -w '%{http_code}' -H "Authorization: Bearer $TOKEN" \
  "https://api.github.com/repos/$REPO/commits/$SHA")
echo "    GET /commits/$SHA → HTTP $KNOWN"

# --------------------------------------------- 5. confirm nothing triggered
say "5 · Confirm no workflow was triggered"
sleep 4
RUNS_AFTER=$(curl -s -H "Authorization: Bearer $TOKEN" \
  "https://api.github.com/repos/$REPO/actions/runs?head_sha=$SHA" \
  | python3 -c 'import json,sys; print(json.load(sys.stdin)["total_count"])')
echo "    workflow runs for this SHA: $RUNS_BEFORE before the push, $RUNS_AFTER after"
if [ "$RUNS_BEFORE" = "$RUNS_AFTER" ]; then
  ok "the temporary ref triggered nothing"
  [ "$RUNS_AFTER" != "0" ] && echo "    (the $RUNS_AFTER existing runs come from the branch push and PR, not from this ref)"
else
  no "count changed — the temporary ref triggered something; investigate"
fi
echo "    refs/local-ci/* is outside refs/heads/* and refs/tags/*, so Actions cannot trigger on it"

# ----------------------------------------------------- 6. publish a receipt
say "6 · Publish the receipt"
curl -s -X POST -H "Authorization: Bearer $TOKEN" \
  "https://api.github.com/repos/$REPO/statuses/$SHA" \
  -d "{\"state\":\"success\",\"context\":\"$CONTEXT\",\"description\":\"$CHECK_PKG passed locally\"}" \
  -o /tmp/poc-status.json -w '    POST /statuses → HTTP %{http_code}\n'

# ------------------------------------------------------- 7. read it back
say "7 · Read it back, as CI would"
curl -s -H "Authorization: Bearer $TOKEN" \
  "https://api.github.com/repos/$REPO/commits/$SHA/status" \
  | python3 -c '
import json,sys
for st in json.load(sys.stdin).get("statuses", []):
    ctx = st["context"]
    if not ctx.startswith("local-ci/"):
        continue
    who = (st.get("creator") or {}).get("login", "<none>")
    print("    " + ctx + "  " + st["state"] + "  creator=" + who)
print("    .github/workflows/poc-local-ci.yml reads exactly this and, when the")
print("    state is success, skips running the package'"'"'s JavaScript job.")
print("    Trust is NOT implemented: the workflow does not yet check that")
print("    creator belongs to a trusted team. That is the next piece.")
'

# -------------------------------------------------------------- 8. cleanup
say "8 · Clean up"
cleanup && TEMP_REF="" && ok "temporary ref removed"
echo "    the status remains on the commit — that is the receipt"
