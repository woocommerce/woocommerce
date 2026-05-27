#!/usr/bin/env bash

# Ported from Jetpack's .github/files/test-plugin-update/test.sh ("Test plugin
# upgrades"), adapted for WooCommerce: no Jetpack connection mocking, php -S
# instead of an Apache container, and a stronger set of assertions.

set -eo pipefail

# Expected environment (set by the workflow):
#   PLUGIN_SLUG  Plugin slug / install directory name (woocommerce).
#   PLUGIN_FILE  Plugin entry file, relative to the plugins dir (woocommerce/woocommerce.php).
#   WP_PATH      Absolute path to the WordPress install / web root.
#   WP_HOST      Host the built-in server binds to (e.g. 127.0.0.1).
#   WP_PORT      Port the built-in server binds to (e.g. 8080).
#   ZIPDIR       Directory holding woocommerce-{stable,trunk,dev}.zip.
#   DB_DUMP      Clean-state database dump to restore before each scenario.

WP=( wp --path="$WP_PATH" )
DEBUG_LOG="$WP_PATH/wp-content/debug.log"
SERVER_LOG="$GITHUB_WORKSPACE/php-server.log"
BASE_URL="http://$WP_HOST:$WP_PORT"
FATAL_RE='PHP (Fatal error|Parse error)|Uncaught (Error|Exception|TypeError|ValueError)'
EXIT=0
FINISHED=false
SERVER_PID=
SCENARIOS_RUN=0
BASELINE_SKIPPED=0

# A skipped scenario (missing zip, or a fatal in the baseline build itself) only
# warns — we don't red-flag a PR for a broken wp.org/nightly source. Total skip
# (zero scenarios actually upgraded) IS a failure, enforced by the zero-guard at
# the end. Partial skip is therefore an accepted, surfaced-but-non-fatal state.

# shellcheck disable=SC2329  # Invoked indirectly via the EXIT trap below.
function onexit {
	if [[ -n "$SERVER_PID" ]]; then
		kill "$SERVER_PID" &>/dev/null || true
	fi
	if ! "$FINISHED"; then
		echo "💣 The testing script exited unexpectedly." >> "$GITHUB_STEP_SUMMARY"
	fi
}
trap onexit EXIT

# Record a failure. Converts only our intentional "\n" separators to real newlines
# and prints the rest literally (no %b escape processing of arbitrary fatal text).
# A real newline is used because this lands in the Markdown step summary; %0A only
# renders as a newline inside ::workflow:: commands.
function failed {
	printf '%s\n' "❌ ${1//\\n/$'\n'}" >> "$GITHUB_STEP_SUMMARY"
	FAILED=1
	EXIT=1
}

# Truncate (creating if absent) the debug log.
function reset_log {
	: > "$DEBUG_LOG"
}

# Echo any logged PHP fatal/parse error or uncaught throwable in the given log
# (default: the WP debug log), empty if clean. Uncaught Errors (the #65337 class)
# are logged by PHP as "PHP Fatal error:  Uncaught Error: ...".
function log_fatal {
	grep -iE "$FATAL_RE" "${1:-$DEBUG_LOG}" 2>/dev/null || true
}

# --- Detector self-test -------------------------------------------------------
# Prove the fatal-detection chain works before trusting any green result. If the
# grep pattern or log path ever drifts, this fails loudly instead of silently
# passing every PR. (setup.sh separately proves WordPress writes to this log.)
echo "::group::Detector self-test"
# Generate a REAL uncaught PHP fatal (same shape as the #65337 missing-class error)
# and confirm log_fatal matches PHP's actual log format. This guards against grep
# pattern drift AND PHP log-format drift — not just a synthetic string we know matches.
SELFTEST_LOG="$( mktemp )"
php -d log_errors=1 -d error_log="$SELFTEST_LOG" -d display_errors=0 -r 'CiDetectorSelfTestMissingClass::go();' || true
if [[ -z "$( log_fatal "$SELFTEST_LOG" )" ]]; then
	echo "::error::Detector self-test failed — the pattern did not match a real PHP fatal."
	cat "$SELFTEST_LOG" || true
	echo "❌ Detector self-test failed; aborting (cannot reliably detect fatals)." >> "$GITHUB_STEP_SUMMARY"
	FINISHED=true  # Suppress the trap's "exited unexpectedly" message on this deliberate abort.
	exit 1
fi
rm -f "$SELFTEST_LOG"
echo "Detector self-test passed."
echo "::endgroup::"

# --- Web server ---------------------------------------------------------------
echo "::group::Starting PHP built-in web server"
# PHP_CLI_SERVER_WORKERS lets the server handle the sub-requests the upgrade
# flow may make without stalling on its single thread.
PHP_CLI_SERVER_WORKERS=4 php -S "$WP_HOST:$WP_PORT" -t "$WP_PATH" > "$SERVER_LOG" 2>&1 &
SERVER_PID=$!
# version.php executes (returns HTTP 200, no DB needed) once PHP is serving.
for (( i=1; i<=15; i++ )); do
	curl -sf -o /dev/null "$BASE_URL/wp-includes/version.php" && break
	[[ $i -eq 15 ]] && { echo "::error::Web server failed to start."; cat "$SERVER_LOG"; exit 1; }
	sleep 1
done
echo "Web server ready (pid $SERVER_PID)."
echo "::endgroup::"

# --- Scenarios ----------------------------------------------------------------
for FROM in stable trunk; do
	for HOW in web cli; do
		[[ -e "$ZIPDIR/$PLUGIN_SLUG-$FROM.zip" ]] || { echo "No $FROM zip, skipping $FROM/$HOW."; continue; }

		FAILED=
		printf '\n\e[1mTest upgrade of %s from %s via %s\e[0m\n' "$PLUGIN_SLUG" "$FROM" "$HOW"

		echo "::group::Restoring database from backup"
		if ! "${WP[@]}" db import "$DB_DUMP"; then
			failed "Database restore failed for $FROM/$HOW!"
		fi
		echo "::endgroup::"
		if [[ -n "$FAILED" ]]; then continue; fi

		echo "::group::Installing baseline $PLUGIN_SLUG $FROM"
		reset_log
		if ! "${WP[@]}" plugin install --activate --force "$ZIPDIR/$PLUGIN_SLUG-$FROM.zip"; then
			failed "Baseline install/activate failed for $PLUGIN_SLUG $FROM!"
		fi
		echo '== Debug log =='
		cat "$DEBUG_LOG"
		BASELINE_FATAL="$( log_fatal )"
		# Remove the sentinel so its reappearance after the upgrade proves the swap.
		rm -f "$WP_PATH/wp-content/plugins/$PLUGIN_SLUG/ci-flag.txt"
		echo "::endgroup::"
		if [[ -n "$FAILED" ]]; then
			rm -rf "$WP_PATH/wp-content/plugins/$PLUGIN_SLUG"
			continue
		fi
		# A fatal in the baseline (the previous release / nightly itself, before any
		# upgrade) is an environment/source problem, not a regression in this PR.
		# Surface it as a warning and skip, rather than red-flagging an innocent PR.
		if [[ -n "$BASELINE_FATAL" ]]; then
			echo "::warning::Baseline $FROM build logged a fatal before upgrade — not attributing to this PR.%0A$BASELINE_FATAL"
			echo "⚠️ Baseline $FROM build fataled before upgrade (environment/source issue, not this PR); skipped $FROM/$HOW." >> "$GITHUB_STEP_SUMMARY"
			BASELINE_SKIPPED=$(( BASELINE_SKIPPED + 1 ))
			"${WP[@]}" plugin uninstall "$PLUGIN_SLUG" 2>/dev/null || rm -rf "$WP_PATH/wp-content/plugins/$PLUGIN_SLUG"
			continue
		fi

		# Count only scenarios that reach the upgrade with a clean baseline, so an
		# all-baseline-skipped run trips the zero-guard (red) instead of going green.
		SCENARIOS_RUN=$(( SCENARIOS_RUN + 1 ))

		echo "::group::Upgrading $PLUGIN_SLUG via $HOW"
		"${WP[@]}" --quiet option set fake_plugin_update_plugin "$PLUGIN_FILE" || true
		"${WP[@]}" --quiet option set fake_plugin_update_url "$ZIPDIR/$PLUGIN_SLUG-dev.zip" || true
		reset_log
		SERVER_FATAL=
		if [[ "$HOW" == 'cli' ]]; then
			if ! "${WP[@]}" plugin upgrade "$PLUGIN_SLUG" 2>&1 | tee "$GITHUB_WORKSPACE/out.txt"; then
				failed "CLI upgrade of $PLUGIN_SLUG from $FROM exited with a non-zero status"
			fi
		else
			# The /wp-admin/update.php iframe flow is what reproduces the same-request
			# autoload race (#65337). curl returns 0 even on HTTP 5xx, so assert on the
			# status code and scan the body for WP's critical-error markers explicitly.
			SERVER_LOG_PRE="$( wc -c < "$SERVER_LOG" 2>/dev/null || echo 0 )"
			HTTP_CODE="$( curl -s --get --connect-timeout 10 --max-time 300 \
				--output "$GITHUB_WORKSPACE/out.txt" --write-out '%{http_code}' \
				--url "$BASE_URL/wp-admin/update.php?action=upgrade-plugin&_wpnonce=bogus" \
				--data "plugin=$PLUGIN_FILE" || true )"
			echo "HTTP $HTTP_CODE"
			cat "$GITHUB_WORKSPACE/out.txt" || true
			echo
			if [[ "$HTTP_CODE" != 2* && "$HTTP_CODE" != 3* ]]; then
				failed "Web upgrade of $PLUGIN_SLUG from $FROM returned HTTP $HTTP_CODE"
			fi
			if grep -qiE 'There has been a critical error|Installation failed|Update failed' "$GITHUB_WORKSPACE/out.txt"; then
				failed "Web upgrade of $PLUGIN_SLUG from $FROM reported an error in the response body"
			fi
			# WordPress routes error_log() to debug.log, so a mid-upgrade fatal lands there;
			# but a fatal raised before WP sets that up would go to php -S's stderr instead.
			# Scan the new tail of the server log too, so neither sink can hide a fatal.
			SERVER_FATAL="$( tail -c "+$(( SERVER_LOG_PRE + 1 ))" "$SERVER_LOG" 2>/dev/null | grep -iE "$FATAL_RE" || true )"
		fi
		echo '== Debug log =='
		cat "$DEBUG_LOG"
		echo "::endgroup::"

		UP_FATAL="$( log_fatal )"
		if [[ -n "$UP_FATAL$SERVER_FATAL" ]]; then
			failed "Mid-upgrade fatal for $PLUGIN_SLUG ($HOW update from $FROM)!\n${UP_FATAL}${SERVER_FATAL}"
			echo "::error::Mid-upgrade fatal for $PLUGIN_SLUG ($HOW from $FROM)."
		elif [[ ! -e "$WP_PATH/wp-content/plugins/$PLUGIN_SLUG/ci-flag.txt" ]]; then
			failed "Plugin $PLUGIN_SLUG ($HOW update from $FROM) does not appear to have been updated (sentinel missing)."
		fi

		# Post-upgrade smoke: even when the upgrade request logged no fatal, confirm the
		# site still serves (catches WSOD / broken bootstrap with no logged fatal). Follow
		# redirects (-L) so WooCommerce's first-load onboarding redirect lands on a real
		# rendered page rather than passing on the bare 30x.
		if [[ -z "$FAILED" ]]; then
			echo "::group::Post-upgrade page-load smoke"
			reset_log
			SERVER_LOG_PRE="$( wc -c < "$SERVER_LOG" 2>/dev/null || echo 0 )"
			HOME_CODE="$( curl -sL --connect-timeout 10 --max-time 60 -o /dev/null --write-out '%{http_code}' "$BASE_URL/" || true )"
			ADMIN_CODE="$( curl -sL --connect-timeout 10 --max-time 60 -o /dev/null --write-out '%{http_code}' "$BASE_URL/wp-admin/" || true )"
			echo "home=$HOME_CODE admin=$ADMIN_CODE"
			cat "$DEBUG_LOG"
			SMOKE_FATAL="$( log_fatal )$( tail -c "+$(( SERVER_LOG_PRE + 1 ))" "$SERVER_LOG" 2>/dev/null | grep -iE "$FATAL_RE" || true )"
			if [[ "$HOME_CODE" != 2* ]]; then
				failed "Home page returned HTTP $HOME_CODE after $HOW upgrade from $FROM"
			fi
			if [[ "$ADMIN_CODE" != 2* ]]; then
				failed "wp-admin returned HTTP $ADMIN_CODE after $HOW upgrade from $FROM"
			fi
			if [[ -n "$SMOKE_FATAL" ]]; then
				failed "Post-upgrade page load fataled ($HOW from $FROM)!\n$SMOKE_FATAL"
			fi
			echo "::endgroup::"
		fi

		echo "::group::Deactivating $PLUGIN_SLUG"
		reset_log
		if ! "${WP[@]}" plugin deactivate "$PLUGIN_SLUG"; then
			failed "Plugin deactivate failed after $PLUGIN_SLUG $HOW update from $FROM!"
		fi
		echo '== Debug log =='
		cat "$DEBUG_LOG"
		if [[ -n "$( log_fatal )" ]]; then
			failed "Fatal on deactivate for $PLUGIN_SLUG ($HOW from $FROM)!"
		fi
		echo "::endgroup::"

		echo "::group::Uninstalling $PLUGIN_SLUG"
		reset_log
		if ! "${WP[@]}" plugin uninstall "$PLUGIN_SLUG"; then
			failed "Plugin uninstall failed after $PLUGIN_SLUG $HOW update from $FROM!"
			rm -rf "$WP_PATH/wp-content/plugins/$PLUGIN_SLUG"
		fi
		echo '== Debug log =='
		cat "$DEBUG_LOG"
		if [[ -n "$( log_fatal )" ]]; then
			failed "Fatal on uninstall for $PLUGIN_SLUG ($HOW from $FROM)!"
		fi
		echo "::endgroup::"

		"${WP[@]}" --quiet option delete fake_plugin_update_plugin || true
		"${WP[@]}" --quiet option delete fake_plugin_update_url || true

		if [[ -z "$FAILED" ]]; then
			echo "✅ Upgrade of $PLUGIN_SLUG from $FROM via $HOW succeeded!" >> "$GITHUB_STEP_SUMMARY"
		fi
	done
done

# Guard against a silent green where no scenario actually exercised an upgrade —
# either every source zip was missing, or every baseline fataled before upgrade.
if [[ "$SCENARIOS_RUN" -eq 0 ]]; then
	echo "::error::No upgrade scenarios ran — every source zip was missing or every baseline fataled before upgrade."
	echo "❌ No upgrade scenarios actually ran (missing source zips, or all baselines fataled)." >> "$GITHUB_STEP_SUMMARY"
	EXIT=1
elif [[ "$BASELINE_SKIPPED" -gt 0 ]]; then
	# Surface partial coverage loss prominently: the run is green but did not test
	# every source. A baseline fatal is an environment/source issue, not this PR's
	# fault, so it doesn't fail the job — but the reduced coverage must be visible.
	echo "::warning::Coverage reduced — $BASELINE_SKIPPED scenario(s) skipped on a baseline fatal; those upgrade paths were NOT tested."
	echo "⚠️ Coverage reduced: $BASELINE_SKIPPED scenario(s) skipped because the baseline build fataled before upgrade. Those upgrade paths were NOT exercised this run." >> "$GITHUB_STEP_SUMMARY"
fi

FINISHED=true
exit $EXIT
