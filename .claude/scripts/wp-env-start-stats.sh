#!/usr/bin/env bash
#
# Track CI jobs whose "Start Test Environment: start" step retried or failed, and keep a
# markdown report up to date.
#
# The step wraps `wp-env start` in a 3-attempt retry loop (added in PR #66491, merged
# 2026-07-15) that annotates every attempt:
#
#   ::warning::wp-env-start-retry reason=<subsystem> attempt=N/3
#   ::error::wp-env start failed after 3 attempts (last reason=<subsystem>)
#
# A job that emitted a retry warning but no failure annotation was recovered by the retry;
# one that emitted the failure annotation was lost. Collection is incremental: each run is
# scanned once, recorded in scanned.txt, and the day cursor advances in state.json.
#
# Usage:
#   wp-env-start-stats.sh                     # collect new runs, then rebuild the report
#   wp-env-start-stats.sh --report-only       # rebuild the report from existing data
#   wp-env-start-stats.sh --since 2026-08-01  # override the resume point for this run
#   wp-env-start-stats.sh --max-days 3        # stop after N days (backfill in chunks)
#   wp-env-start-stats.sh --reset             # discard collected data and start over
#
# The cause of each failure is worked out at collection time and stored, so editing
# classify() only affects runs scanned after the edit. Use --reset to re-derive the lot.
#
set -uo pipefail

SELF=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)/$(basename -- "${BASH_SOURCE[0]}")
REPO="woocommerce/woocommerce"
# The retry loop and its annotations landed here. Nothing before this date is measurable.
START_DATE="2026-07-15"
# Causes outside this project's control: a third-party outage, or a branch that does not
# build. Losses from these are excluded from the adjusted recovery rate, so a bad
# afternoon upstream does not read as wp-env instability. Keep in step with classify().
# `workspace-eacces` is deliberately absent: it reads like a broken branch, but it is our
# own wp-env mapping left root-owned by Docker, which our retry then cannot clean.
NOT_OURS="github-api plugin-code"
# Workflows that call the reusable ci.yml and therefore run the wp-env start step.
WORKFLOWS=".github/workflows/ci.yml .github/workflows/tests-on-release.yml .github/workflows/tests-on-demand.yml"
PARALLEL=6
# Runs are listed by creation date but only counted once complete, and a nightly can take
# hours. Re-listing a few days behind the cursor picks up runs that finished after their
# own day had already been scanned; scanned.txt makes the repeat listings nearly free.
LOOKBACK_DAYS=3

here=$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)
DATA_DIR="$here/../ci-monitoring"
mkdir -p "$DATA_DIR"
EVENTS="$DATA_DIR/events.tsv"
SCANNED="$DATA_DIR/scanned.txt"
STATE="$DATA_DIR/state.json"
REPORT="$DATA_DIR/wp-env-start-failures.md"
# Hand-written prose, appended verbatim so it survives regeneration.
ANALYSIS="$DATA_DIR/analysis.md"

report_only=0
since_override=""
max_days=0
worker=0
reset=0
while [ $# -gt 0 ]; do
  case "$1" in
    --report-only) report_only=1 ;;
    --since)       since_override="${2:-}"; shift ;;
    --max-days)    max_days="${2:-0}"; shift ;;
    --reset)       reset=1 ;;
    # Internal: scan a single run. The collector re-invokes this script rather than
    # exporting a shell function, because exported functions do not survive the hop
    # reliably across bash versions.
    --scan-one)    worker=1; shift; break ;;
    -h|--help)     sed -n '2,25p' "$0"; exit 0 ;;
    *) echo "unknown argument: $1" >&2; exit 2 ;;
  esac
  shift
done

command -v gh    >/dev/null || { echo "gh CLI is required" >&2; exit 1; }
command -v jq    >/dev/null || { echo "jq is required" >&2; exit 1; }
command -v unzip >/dev/null || { echo "unzip is required" >&2; exit 1; }
# Scope the check to github.com: a bare `gh auth status` also probes any enterprise host
# configured, and one of those timing out would fail this check for no reason.
gh auth status --hostname github.com >/dev/null 2>&1 \
  || { echo "gh is not authenticated for github.com; run: gh auth login" >&2; exit 1; }

if [ "$reset" -eq 1 ]; then
  rm -f "$EVENTS" "$SCANNED" "$STATE"
  echo "Discarded collected data; the next collection starts from $START_DATE."
fi

touch "$EVENTS" "$SCANNED"

next_day() { date -u -j -v+1d -f %F "$1" +%F 2>/dev/null || date -u -d "$1 + 1 day" +%F; }
back_days() { date -u -j -v-"$2"d -f %F "$1" +%F 2>/dev/null || date -u -d "$1 - $2 days" +%F; }

# Root cause of a failed final attempt, most specific first. Case-sensitive unless the
# source text genuinely varies, so that e.g. a JS status enum containing "TooManyRequests"
# cannot masquerade as Docker Hub rate limiting.
classify() {
  local s="$1"
  if   grep -qaiE 'PHP Parse error|Parse error: syntax error' "$s"; then echo plugin-code
  elif grep -qaE  'Could not open input file: /tmp/composer-setup\.php' "$s"; then echo composer-installer
  # got@11 is only used by wp-env to fetch core, plugin and theme zips from wordpress.org,
  # so any transport or 5xx error raised through it belongs to that subsystem.
  elif grep -qaE  'AggregateError \[ETIMEDOUT\]|RequestError: read ECONNRESET' "$s"; then echo wordpress-org
  elif grep -qaE  'HTTPError: Response code 429' "$s"; then echo wordpress-org-ratelimit
  elif grep -qaE  'HTTPError: Response code 5[0-9][0-9]' "$s"; then echo wordpress-org
  elif grep -qaiE 'socket hang up' "$s"; then echo wordpress-org
  elif grep -qaE  'curl error 28|could not be fully loaded' "$s"; then echo packagist
  elif grep -qaE  'toomanyrequests: You have reached your pull rate limit' "$s"; then echo dockerhub-ratelimit
  elif grep -qaE  'registry-1\.docker\.io|failed to resolve reference|httpReadSeeker: failed open|auth\.docker\.io' "$s"; then echo dockerhub
  elif grep -qaiE 'is unhealthy' "$s"; then echo container-unhealthy
  elif grep -qaE  'EACCES: permission denied' "$s"; then echo workspace-eacces
  elif grep -qaiE 'Cannot connect to the Docker daemon' "$s"; then echo docker-daemon
  elif grep -qaiE 'afterStart Error' "$s"; then echo wp-env-afterstart
  # Composer fetching dists inside the Docker build. Must stay above the buildkit rule:
  # BuildKit reports it as "failed to solve", which hides the upstream that actually failed.
  elif grep -qaE  'api\.github\.com.*file could not be downloaded' "$s"; then echo github-api
  elif grep -qaiE 'failed to solve' "$s"; then echo buildkit
  elif grep -qaiE 'Error while running docker compose command' "$s"; then echo docker-compose
  else echo unclassified
  fi
}

# One run -> zero or more event lines, written to $OUTDIR/<run>.tsv so parallel workers
# never interleave into a shared file.
scan_run() {
  local run="$1" created="$2" wf="$3" branch="$4" ev="$5" att="${6:-1}"
  [ -z "$run" ] && return 0
  local z d out
  out="$OUTDIR/$run-$att.tsv"
  z=$(mktemp) || return 0
  d=$(mktemp -d) || { rm -f "$z"; return 0; }
  # One API call returns every job log for the attempt -- ~20x cheaper than fetching
  # annotations per job. Always address the attempt explicitly: the bare /logs endpoint
  # serves only the latest attempt, so a re-run would hide every failure that caused it.
  local ok=0 try err
  err=$(mktemp)
  for try in 1 2 3; do
    if gh api "repos/$REPO/actions/runs/$run/attempts/$att/logs" > "$z" 2>"$err"; then
      unzip -qq -o "$z" -d "$d" 2>/dev/null && { ok=1; break; }
      # A run stopped before any job wrote a log still serves 200 with a zero-entry zip,
      # which unzip rejects. Nothing to read, and never will be -- count it as read, or
      # the attempt stays out of the seen-set and every later collection fetches it again.
      # An archive holding entries opens with the local file header signature PK\x03\x04;
      # one holding none opens straight with the end-of-central-directory record PK\x05\x06.
      # Read the signature rather than unzip's wording, and rather than the run conclusion:
      # most of these runs were cancelled, but some conclude `failure`.
      [ "$(od -An -tx1 -N4 "$z" | tr -d ' ')" = "504b0506" ] && { ok=2; break; }
    fi
    # 404 means there is nothing to read and never will be -- most of these are runs held
    # at `action_required` that never started a job. Count them as read; retrying them
    # every collection is pure waste.
    if grep -q 'HTTP 404' "$err"; then ok=2; break; fi
    sleep $((try * 3))
  done
  rm -f "$err"
  if [ "$ok" -eq 0 ]; then
    # Leave a marker so the collector does not record this attempt as scanned. Treating a
    # failed download as "nothing to report" silently drops every event in the run and
    # never looks at it again.
    : > "$OUTDIR/$run-$att.fail"
    rm -rf "$z" "$d"; return 0
  fi
  [ "$ok" -eq 2 ] && { rm -rf "$z" "$d"; return 0; }

  # Top-level *.txt entries are the per-job logs; subdirectories repeat them per step.
  find "$d" -maxdepth 1 -name '*.txt' -print0 2>/dev/null | while IFS= read -r -d '' f; do
    # Anchor on the ##[warning]/##[error] prefixes: GitHub also echoes the step's own bash
    # source into the log, and that source contains these strings verbatim, unprefixed.
    grep -qa '##\[warning\]wp-env-start-retry' "$f" || continue

    local reason attempts outcome cause job n e slice
    reason=$(grep -ao '##\[warning\]wp-env-start-retry reason=[a-z]*' "$f" | tail -1 | sed 's/.*reason=//')
    attempts=$(grep -ac '##\[warning\]wp-env-start-retry' "$f")
    job=$(basename "$f" .txt | sed -E 's/^[0-9]+_//' | tr '\t|' '  ')

    if grep -qa '##\[error\]wp-env start failed after' "$f"; then
      outcome=LOST
      # Classify on the final attempt only: the slice between the last retry warning and
      # the failure annotation. Matching the whole log picks up the asset build output and
      # the docker daemon debug dump, both of which produce false positives.
      n=$(grep -an '##\[warning\]wp-env-start-retry' "$f" | tail -1 | cut -d: -f1)
      e=$(grep -an '##\[error\]wp-env start failed after' "$f" | tail -1 | cut -d: -f1)
      slice=$(mktemp)
      awk -v N="${n:-1}" -v E="${e:-999999999}" 'NR>N && NR<E' "$f" \
        | grep -av 'level=warning' | grep -av 'level=info' > "$slice"
      cause=$(classify "$slice")
      rm -f "$slice"
    else
      outcome=SAVED
      cause=-
    fi
    printf '%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\n' \
      "$run" "$created" "$wf" "$branch" "$ev" "$job" "$outcome" "$reason" "$attempts" "$cause" "$att" >> "$out"
  done
  rm -rf "$z" "$d"
}

if [ "$worker" -eq 1 ]; then
  scan_run "${1:-}" "${2:-}" "${3:-}" "${4:-}" "${5:-}" "${6:-1}"
  exit 0
fi

# ---------------------------------------------------------------- collect
if [ "$report_only" -eq 0 ]; then
  # Resolve workflow ids by file path, so a renamed workflow surfaces as an error rather
  # than as silently missing data.
  wf_ids=$(gh api -X GET "repos/$REPO/actions/workflows" -f per_page=100 \
    --jq '.workflows[] | [.path, (.id|tostring)] | @tsv' 2>/dev/null)
  ids=""
  for p in $WORKFLOWS; do
    id=$(printf '%s\n' "$wf_ids" | awk -F'\t' -v P="$p" '$1==P{print $2; exit}')
    [ -z "$id" ] && { echo "workflow not found: $p" >&2; exit 1; }
    ids="$ids $id"
  done

  cursor="$START_DATE"
  [ -s "$STATE" ] && cursor=$(jq -r '.cursor // empty' "$STATE" 2>/dev/null)
  [ -z "$cursor" ] && cursor="$START_DATE"
  # High-water mark. The lookback below rewinds where we *read* from; it must never rewind
  # what we have already covered, or --max-days would walk the cursor backwards forever.
  high_water="$cursor"
  if [ -n "$since_override" ]; then
    cursor="$since_override"
  else
    cursor=$(back_days "$cursor" "$LOOKBACK_DAYS")
    [ "$cursor" \< "$START_DATE" ] && cursor="$START_DATE"
  fi

  today=$(date -u +%F)
  day="$cursor"
  days_done=0
  echo "Collecting from $day to $today"

  while [ "$day" \< "$today" ] || [ "$day" = "$today" ]; do
    # Day-sized windows keep every query under the API's 1000-result ceiling, so no run
    # is silently dropped on a busy day.
    runs=$(for id in $ids; do
      for page in 1 2 3 4 5 6 7 8 9 10; do
        out=$(gh api -X GET "repos/$REPO/actions/workflows/$id/runs" \
          -f created="$day" -f status=completed -f per_page=100 -f page="$page" \
          --jq '.workflow_runs[] | [(.id|tostring), .created_at, .name, .head_branch, .event, (.run_attempt|tostring)] | @tsv' 2>/dev/null)
        [ -z "$out" ] && break
        printf '%s\n' "$out"
        [ "$(printf '%s\n' "$out" | wc -l)" -lt 100 ] && break
      done
    done)

    if [ -n "$runs" ]; then
      # Skip runs already recorded, so an interrupted collection resumes for free.
      # Read the seen-set with getline rather than the usual NR==FNR idiom: when the first
      # file is empty, NR==FNR stays true for every line of the second and silently drops
      # all of them.
      # Expand each run into one line per attempt, keyed "<run>:<attempt>". A re-run keeps
      # its run id, so keying the seen-set on the id alone would dedupe away every attempt
      # after the first -- and the failures that prompted the re-run are exactly the ones
      # worth counting.
      expanded=$(printf '%s\n' "$runs" | awk -F'\t' \
        '{n=($6==""?1:$6+0); for(i=1;i<=n;i++) print $1":"i"\t"$0"\t"i}')
      new=$(printf '%s\n' "$expanded" | awk -F'\t' -v f="$SCANNED" \
        'BEGIN{while((getline l < f) > 0) seen[l]=1} !($1 in seen)')
      count=$(printf '%s' "$new" | grep -c . || true)
      if [ "${count:-0}" -gt 0 ]; then
        echo "  $day: $count new run-attempt(s)"
        OUTDIR=$(mktemp -d); export OUTDIR
        # Fan out in fixed-size batches. Passing the fields as separate arguments matters:
        # `xargs -I` collapses the tabs in a TSV line into spaces, which silently merges
        # every field into one.
        inflight=0
        while IFS=$'\t' read -r r_key r_id r_created r_wf r_branch r_ev r_natt r_att; do
          [ -z "$r_id" ] && continue
          "$SELF" --scan-one "$r_id" "$r_created" "$r_wf" "$r_branch" "$r_ev" "$r_att" &
          inflight=$((inflight + 1))
          if [ "$inflight" -ge "$PARALLEL" ]; then wait; inflight=0; fi
        done <<< "$new"
        wait
        cat "$OUTDIR"/*.tsv >> "$EVENTS" 2>/dev/null
        # Record only the attempts whose log actually downloaded and parsed. Anything that
        # left a .fail marker stays out of the seen-set, so the next collection retries it.
        printf '%s\n' "$new" | cut -f1 | while read -r k; do
          [ -z "$k" ] && continue
          [ -f "$OUTDIR/$(printf '%s' "$k" | tr ':' '-').fail" ] || printf '%s\n' "$k"
        done >> "$SCANNED"
        failed=$(find "$OUTDIR" -name '*.fail' 2>/dev/null | grep -c . || true)
        [ "${failed:-0}" -gt 0 ] \
          && echo "    $failed attempt(s) failed to download; queued for the next run"
        rm -rf "$OUTDIR"
      else
        echo "  $day: up to date"
      fi
    else
      echo "  $day: no runs"
    fi

    # Only advance past a day once it is fully scanned.
    [ "$day" \> "$high_water" ] && high_water="$day"
    printf '{\n  "cursor": "%s",\n  "last_collected_at": "%s"\n}\n' \
      "$high_water" "$(date -u +%FT%TZ)" > "$STATE"

    days_done=$((days_done + 1))
    if [ "$max_days" -gt 0 ] && [ "$days_done" -ge "$max_days" ]; then
      echo "Stopped after $days_done day(s) (--max-days)."
      break
    fi
    [ "$day" = "$today" ] && break
    day=$(next_day "$day")
  done

  sort -u -o "$SCANNED" "$SCANNED"
  sort -u -o "$EVENTS" "$EVENTS"
fi

# ---------------------------------------------------------------- report
count_lines() { [ -s "$1" ] && grep -c . "$1" || echo 0; }
# SCANNED holds one "<run>:<attempt>" key per line, so count distinct run ids.
runs_scanned=$(cut -d: -f1 "$SCANNED" 2>/dev/null | sort -u | grep -c . || echo 0)
last_run=$(cut -d: -f1 "$SCANNED" 2>/dev/null | sort -n | tail -1)
last_day=$(cut -f2 "$EVENTS" | cut -c1-10 | sort | tail -1)
cursor_now=$([ -s "$STATE" ] && jq -r '.cursor // "-"' "$STATE" || echo "-")

# Every rollup comes out of one awk pass so the day, week, month and total tables can
# never disagree with each other.
ROLLUPS=$(awk -F'\t' -v start="$START_DATE" '
  # days since 1970-01-01, for bucketing a date into its ISO week (Monday start).
  function days(y, m, d,   a, yy, mm) {
    a = int((14 - m) / 12); yy = y + 4800 - a; mm = m + 12 * a - 3
    return d + int((153 * mm + 2) / 5) + 365 * yy + int(yy / 4) - int(yy / 100) + int(yy / 400) - 32045 - 2440588
  }
  function monday(iso,   y, m, d, n) {
    y = substr(iso, 1, 4) + 0; m = substr(iso, 6, 2) + 0; d = substr(iso, 9, 2) + 0
    n = days(y, m, d)
    return n - ((n + 3) % 7)     # 1970-01-01 was a Thursday
  }
  function isodate(n,   a, b, c, dd, e, m, day, mon, yr) {
    a = n + 2440588 + 32044; b = int((4 * a + 3) / 146097); c = a - int(146097 * b / 4)
    dd = int((4 * c + 3) / 1461); e = c - int(1461 * dd / 4); m = int((5 * e + 2) / 153)
    day = e - int((153 * m + 2) / 5) + 1; mon = m + 3 - 12 * int(m / 10); yr = 100 * b + dd - 4800 + int(m / 10)
    return sprintf("%04d-%02d-%02d", yr, mon, day)
  }
  {
    iso = substr($2, 1, 10); reason = $8; won = ($7 == "SAVED")
    key["total"] = "since " start
    key["month"] = substr($2, 1, 7)
    key["week"]  = isodate(monday(iso))
    key["day"]   = iso
    reasons[reason] = 1
    for (g in key) {
      k = g SUBSEP key[g]
      tot[k]++; if (won) rec[k]++
      tr[k SUBSEP reason]++; if (won) rr[k SUBSEP reason]++
      seen[g SUBSEP key[g]] = key[g]
    }
  }
  END {
    n = 0; for (r in reasons) cols[++n] = r
    for (i = 1; i < n; i++) for (j = i + 1; j <= n; j++) if (cols[i] > cols[j]) { t = cols[i]; cols[i] = cols[j]; cols[j] = t }
    printf "COLS"; for (i = 1; i <= n; i++) printf "\t%s", cols[i]; printf "\n"
    for (s in seen) {
      split(s, p, SUBSEP); g = p[1]; k = seen[s]
      printf "%s\t%s\t%d\t%d", g, k, tot[s], rec[s] + 0
      for (i = 1; i <= n; i++) printf "\t%d/%d", rr[s SUBSEP cols[i]] + 0, tr[s SUBSEP cols[i]] + 0
      printf "\n"
    }
  }' "$EVENTS")

COLS=$(printf '%s\n' "$ROLLUPS" | awk -F'\t' '$1=="COLS"{$1=""; sub(/^\t/,""); print}')

emit_table() {  # emit_table <group> <first-column-header> <limit|0>
  local g="$1" head="$2" limit="$3" line hdr sep
  hdr="| $head | Retried | Recovered | Recovery Rate |"
  sep="|---|---:|---:|---:|"
  local c
  for c in $COLS; do hdr="$hdr \`$c\` |"; sep="$sep---:|"; done
  echo "$hdr"; echo "$sep"
  line=$(printf '%s\n' "$ROLLUPS" | awk -F'\t' -v G="$g" '$1==G' | sort -r -k2)
  [ "$limit" -gt 0 ] && line=$(printf '%s\n' "$line" | head -"$limit")
  printf '%s\n' "$line" | awk -F'\t' 'NF>3{
    printf "| %s | %d | %d | %.0f%% |", $2, $3, $4, ($3 ? 100*$4/$3 : 0)
    for (i = 5; i <= NF; i++) printf " %s |", ($i == "0/0" ? "–" : $i)
    printf "\n" }'
}

{
  echo "# wp-env start: retries and failures"
  echo
  echo "Generated by \`.claude/scripts/wp-env-start-stats.sh\` — do not edit by hand."
  echo "Write prose in \`.claude/ci-monitoring/analysis.md\`; it is appended at the end."
  echo
  echo "| | |"
  echo "|---|---|"
  echo "| Window | $START_DATE (retry merged, PR #66491) → ${last_day:-–} |"
  echo "| Runs scanned | $runs_scanned |"
  echo "| Last run tracked | [$last_run](https://github.com/$REPO/actions/runs/$last_run) |"
  echo "| Collection cursor | $cursor_now |"
  echo "| Updated | $(date -u +%FT%TZ) |"
  echo
  echo "A job is **recovered** when it emitted a retry warning and then passed, and lost"
  echo "when it exhausted every attempt. Jobs that started cleanly first time are not"
  echo "counted — the denominator throughout is jobs that hit a problem at all. Per-reason"
  echo "cells read *recovered / retried*."
  echo
  echo "## Since monitoring started"
  echo
  emit_table total "Window" 0
  echo
  awk -F'\t' -v skip="$NOT_OURS" '
    BEGIN {
      n = split(skip, a, " ")
      for (i = 1; i <= n; i++) { ext[a[i]] = 1; list = list (i > 1 ? ", " : "") "`" a[i] "`" }
    }
    { tot++; if ($7 == "SAVED") rec++; else if ($10 in ext) drop++ }
    END {
      adj = tot - drop
      if (adj > 0) {
        printf "Excluding the %d losses this project cannot fix — a third-party outage, or a\n", drop + 0
        printf "branch that does not build — the rate is **%.0f%%** (%d of %d).\n", 100 * rec / adj, rec, adj
        printf "\n"
        printf "Excluded causes: %s.\n", list
        printf "\n"
        printf "A cause is only recorded for a lost job, so jobs that hit the same outages and\n"
        printf "then recovered still count in the numerator; read the adjusted rate as an upper\n"
        printf "bound.\n"
      }
    }' "$EVENTS"
  echo
  echo "### By reason"
  echo
  echo "\`Share\` is that reason's slice of all retries; \`Recovery Rate\` is how often it recovered."
  echo
  echo "| \`reason=\` | Retried | Share | Recovered | Recovery Rate |"
  echo "|---|---:|---:|---:|---:|"
  awk -F'\t' '{t[$8]++; n++; if($7=="SAVED") s[$8]++}
    END{for(r in t) printf "| `%s` | %d | %.0f%% | %d | %.0f%% |\n",
      r, t[r], (n ? 100*t[r]/n : 0), s[r]+0, 100*(s[r]+0)/t[r]}' \
    "$EVENTS" | sort -t'|' -k3 -rn
  # Totals go out after the sort so the row always lands at the bottom.
  awk -F'\t' '{n++; if($7=="SAVED") s++}
    END{if(n) printf "| **All** | **%d** | **100%%** | **%d** | **%.0f%%** |\n", n, s+0, 100*(s+0)/n}' \
    "$EVENTS"
  echo
  echo "## By month"
  echo
  emit_table month "Month" 0
  echo
  echo "## By week"
  echo
  echo "Weeks start Monday, labelled by that date. Last 12."
  echo
  emit_table week "Week of" 12
  echo
  echo "## By day"
  echo
  echo "Last 30 days with activity."
  echo
  emit_table day "Day" 30
  echo
  echo "## Types of failure"
  echo
  echo "Cause is read from the final attempt's log, not from the \`reason=\` tag — the two"
  echo "disagree often, and most \`unknown\` failures are a branch's own broken code rather"
  echo "than infrastructure. One example per type, the most recent."
  echo
  echo "| Cause | Jobs | Share | Retryable | Latest | \`reason=\` | Branch | Job | Run |"
  echo "|---|---:|---:|---|---|---|---|---|---|"
  awk -F'\t' -v repo="$REPO" '
    function retryable(c) {
      if (c == "plugin-code" || c == "workspace-eacces") return "no — the branch is broken"
      if (c == "composer-installer") return "no — BuildKit caches the bad layer"
      if (c == "unclassified") return "unknown — add a pattern"
      return "yes"
    }
    $7=="LOST" {
      c[$10]++; lost++
      # ISO-8601 timestamps compare correctly as strings, so the newest wins.
      if ($2 > when[$10]) { when[$10]=$2; d[$10]=substr($2,1,10); rs[$10]=$8; br[$10]=$4; jb[$10]=$6; rn[$10]=$1 }
    }
    END {
      for (k in c)
        printf "%d\t| `%s` | %d | %.0f%% | %s | %s | `%s` | `%s` | %s | [%s](https://github.com/%s/actions/runs/%s) |\n",
          c[k], k, c[k], (lost ? 100*c[k]/lost : 0), retryable(k), d[k], rs[k], br[k], jb[k], rn[k], repo, rn[k]
    }' "$EVENTS" | sort -rn | cut -f2-
  echo
  echo "## Most recent failures"
  echo
  echo "| Day | Cause | \`reason=\` | Branch | Job | Run |"
  echo "|---|---|---|---|---|---|"
  awk -F'\t' -v repo="$REPO" '$7=="LOST"{
      printf "| %s | `%s` | `%s` | `%s` | %s | [%s](https://github.com/%s/actions/runs/%s) |\n",
        substr($2,1,10), $10, $8, $4, $6, $1, repo, $1 }' "$EVENTS" \
    | sort -r | head -15
  echo
  echo "Links are run-level: open the run and the failed job is the red one."
  if [ -f "$ANALYSIS" ]; then
    echo
    cat "$ANALYSIS"
  fi
} > "$REPORT"

tot=$(count_lines "$EVENTS")
sav=$(awk -F'\t' '$7=="SAVED"' "$EVENTS" | grep -c . || true)
sav=${sav:-0}
echo
echo "Runs scanned:  $runs_scanned"
echo "Jobs retried:  $tot  ($sav recovered, $((tot - sav)) lost)"
echo "Last run:      $last_run"
echo "Report:        $REPORT"
