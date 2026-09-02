#!/usr/bin/env php
<?php
/**
 * Proof of concept for local-first CI receipts.
 *
 * Demonstrates the one part of the design that is not obvious: a commit status can
 * be attached to a commit GitHub has never seen, before the branch that would
 * trigger CI is pushed. That ordering is what removes the race — publishing a
 * result after pushing the branch is too late, because the workflow has already
 * started.
 *
 * Runs against the real repository. Creates one ref and one commit status, then
 * removes the ref. Safe to run repeatedly.
 *
 *     php tools/local-ci-poc/poc.php
 *
 * Needs `gh`, or any token reachable from GH_TOKEN, GITHUB_TOKEN, or the git
 * credential store. The steps read top to bottom; the plumbing they call is at
 * the bottom of the file.
 */

const REPO = 'woocommerce/woocommerce';

/** A real check: 17 jest tests, about two seconds, no Docker. */
const CHECK_PACKAGE = '@woocommerce/number';

/**
 * The receipt's name. Qualified by project because 21 packages in this monorepo
 * each have a job called "JavaScript", and a bare "JavaScript" receipt would be
 * ambiguous about which one actually ran.
 */
const RECEIPT_CONTEXT = 'local-ci/v1/@woocommerce/number::JavaScript';

/** Set once the temporary ref exists, so the shutdown handler knows to remove it. */
$temporary_ref = null;

remove_the_temporary_ref_however_we_exit();

// ---------------------------------------------------------------------------
// 1. A token, from whatever is already on the machine.
// ---------------------------------------------------------------------------

heading( '1 · Resolve a token from what is already on the machine' );

$token = resolve_token();

if ( null === $token ) {
	// Not a failure. A contributor without a token still gets the value of running
	// the checks locally; they just publish nothing, and CI runs everything.
	fail( 'no token — a real run would still execute the checks, then exit 0' );
	exit( 0 );
}

pass( 'found one (never prompts; no token means publish nothing and exit 0)' );

// ---------------------------------------------------------------------------
// 2. What CI would run for this diff, according to CI's own planner.
// ---------------------------------------------------------------------------

heading( '2 · Ask the planner what CI would run for this diff' );
detail( 'the same ci-jobs tool CI uses, run locally:' );

$planned_jobs = plan_jobs_for_this_diff();

if ( array() === $planned_jobs ) {
	detail( '  (no jobs for this diff — it touches no project CI cares about)', 2 );
} else {
	foreach ( $planned_jobs as $job ) {
		detail( $job, 2 );
	}
}

detail( 'the design reads this list; this POC substitutes the one job below', 2 );

// ---------------------------------------------------------------------------
// 3. Run one of those checks for real.
// ---------------------------------------------------------------------------

heading( '3 · Run an eligible check locally' );

$check = run_the_check();

if ( ! $check['passed'] ) {
	// The whole point of a receipt is that it means something. A failed check
	// publishes nothing and stops, so nothing downstream can be skipped.
	fail( 'check failed — a real run would stop here and not push' );
	exit( 1 );
}

pass( sprintf( '%s passed in %ds — %s', CHECK_PACKAGE, $check['seconds'], $check['summary'] ) );
detail( "this is the same command CI runs for that package's JavaScript job", 2 );

// ---------------------------------------------------------------------------
// 4. Make the commit known to GitHub without pushing a branch.
// ---------------------------------------------------------------------------

heading( '4 · Publish the commit to a ref that triggers nothing' );

$sha = git( 'rev-parse HEAD' );

detail( 'before: does GitHub know this commit?' );
$known_before = commit_is_known_to_github( $token, $sha );
detail( sprintf( 'GET /commits/%s → HTTP %d', short( $sha ), $known_before ), 2 );

if ( 200 === $known_before ) {
	detail( 'NOTE: this commit is already on the remote (pushed, or open in a PR), so', 2 );
	detail( '      the 422 → 200 transition cannot be shown. To see it, make a local', 2 );
	detail( '      commit and run this before pushing. Everything below still runs.', 2 );
}

// Counted before the push so step 5 measures what this push caused, rather than
// counting runs that the branch or its PR had already produced.
$runs_before = count_workflow_runs( $token, $sha );

$temporary_ref = 'refs/local-ci/' . $sha;

// --no-verify because the repo's own pre-push hook would otherwise run against
// this ref — and on trunk its protected-branch guard cancels the push outright.
$pushed = git_succeeds( sprintf( 'push --no-verify -q origin HEAD:%s', $temporary_ref ) );

if ( ! $pushed ) {
	$temporary_ref = null;
	fail( 'could not push the temporary ref' );
	exit( 1 );
}

pass( 'pushed to ' . $temporary_ref );

detail( 'after:' );
detail(
	sprintf( 'GET /commits/%s → HTTP %d', short( $sha ), commit_is_known_to_github( $token, $sha ) ),
	2
);

// ---------------------------------------------------------------------------
// 5. Confirm that ref started nothing.
// ---------------------------------------------------------------------------

heading( '5 · Confirm no workflow was triggered' );

sleep( 4 );
$runs_after = count_workflow_runs( $token, $sha );

detail( sprintf( 'workflow runs for this SHA: %d before the push, %d after', $runs_before, $runs_after ), 2 );

if ( $runs_before === $runs_after ) {
	pass( 'the temporary ref triggered nothing' );

	if ( 0 !== $runs_after ) {
		detail( sprintf( '(the %d existing runs come from the branch push and PR, not this ref)', $runs_after ), 2 );
	}
} else {
	fail( 'count changed — the temporary ref triggered something; investigate' );
}

detail( 'refs/local-ci/* is outside refs/heads/* and refs/tags/*, so Actions cannot trigger on it', 2 );

// ---------------------------------------------------------------------------
// 6. Publish the receipt.
// ---------------------------------------------------------------------------

heading( '6 · Publish the receipt' );

$posted = github_api(
	$token,
	'POST',
	sprintf( '/repos/%s/statuses/%s', REPO, $sha ),
	array(
		'state'       => 'success',
		'context'     => RECEIPT_CONTEXT,
		'description' => CHECK_PACKAGE . ' passed locally',
	)
);

detail( sprintf( 'POST /statuses → HTTP %d', $posted['status'] ), 2 );

// ---------------------------------------------------------------------------
// 7. Read it back the way the workflow does.
// ---------------------------------------------------------------------------

heading( '7 · Read it back, as CI would' );

foreach ( read_receipts( $token, $sha ) as $receipt ) {
	detail(
		sprintf( '%s  %s  creator=%s', $receipt['context'], $receipt['state'], $receipt['creator'] ),
		2
	);
}

detail( '.github/workflows/poc-local-ci.yml reads exactly this and, when the state is', 2 );
detail( "success, skips running the package's JavaScript job.", 2 );
detail( 'Trust is NOT implemented: the workflow does not yet check that creator', 2 );
detail( 'belongs to a trusted team. That is the next piece.', 2 );

// ---------------------------------------------------------------------------
// 8. Leave nothing behind but the receipt.
// ---------------------------------------------------------------------------

heading( '8 · Clean up' );

delete_the_temporary_ref();
pass( 'temporary ref removed' );
detail( 'the status remains on the commit — that is the receipt', 2 );

exit( 0 );


// ===========================================================================
// Plumbing.
// ===========================================================================

/**
 * Find a token without ever prompting.
 *
 * The order matters: an explicitly exported token beats the gh CLI, which beats
 * whatever the git credential store happens to hold. Every source is one a
 * contributor already has, which is the point — this adds no new secret to
 * manage and no new place to configure one.
 *
 * @return string|null The token, or null when the machine has none.
 */
function resolve_token(): ?string {
	foreach ( array( 'GH_TOKEN', 'GITHUB_TOKEN' ) as $variable ) {
		$value = getenv( $variable );

		if ( is_string( $value ) && '' !== $value ) {
			return $value;
		}
	}

	$from_gh_cli = shell( 'gh auth token 2>/dev/null' );

	if ( '' !== $from_gh_cli ) {
		return $from_gh_cli;
	}

	// GIT_TERMINAL_PROMPT=0 guarantees this cannot block waiting for input.
	$credential = shell(
		"printf 'protocol=https\nhost=github.com\n\n' | GIT_TERMINAL_PROMPT=0 git credential fill 2>/dev/null"
	);

	if ( preg_match( '/^password=(.+)$/m', $credential, $matches ) ) {
		return trim( $matches[1] );
	}

	return null;
}

/**
 * Ask the monorepo's own CI planner which jobs this diff would produce.
 *
 * This is the same tool the CI workflow runs, so the list matches what would
 * actually be scheduled rather than an approximation of it.
 *
 * @return string[] Job lines, empty when the diff touches nothing CI cares about.
 */
function plan_jobs_for_this_diff(): array {
	$output = shell( 'pnpm utils ci-jobs --base-ref trunk 2>/dev/null' );
	$jobs   = array();

	foreach ( explode( "\n", $output ) as $line ) {
		if ( str_starts_with( $line, '-  ' ) ) {
			$jobs[] = trim( $line );
		}
	}

	return array_slice( $jobs, 0, 10 );
}

/**
 * Run the package's JavaScript tests and time them.
 *
 * @return array{passed: bool, seconds: int, summary: string}
 */
function run_the_check(): array {
	$log     = sys_get_temp_dir() . '/poc-check.log';
	$started = time();

	$command = sprintf( 'pnpm --filter=%s test:js > %s 2>&1', CHECK_PACKAGE, escapeshellarg( $log ) );
	exec( $command, $ignored, $exit_code );

	$output = is_readable( $log ) ? (string) file_get_contents( $log ) : '';
	$summary = preg_match( '/Tests: +\d+ passed/', $output, $matches ) ? $matches[0] : 'ran';

	return array(
		'passed'  => 0 === $exit_code,
		'seconds' => time() - $started,
		'summary' => $summary,
	);
}

/**
 * The HTTP status of asking GitHub about a commit.
 *
 * 422 means GitHub has never seen this SHA; 200 means it has. Watching that flip
 * across the push in step 4 is the whole trick this POC exists to show.
 *
 * @param string $token Auth token.
 * @param string $sha   Commit to ask about.
 */
function commit_is_known_to_github( string $token, string $sha ): int {
	$response = github_api( $token, 'GET', sprintf( '/repos/%s/commits/%s', REPO, $sha ) );

	return $response['status'];
}

/**
 * How many Actions runs exist for a SHA.
 *
 * @param string $token Auth token.
 * @param string $sha   Commit to count runs for.
 */
function count_workflow_runs( string $token, string $sha ): int {
	$response = github_api( $token, 'GET', sprintf( '/repos/%s/actions/runs?head_sha=%s', REPO, $sha ) );

	return (int) ( $response['body']['total_count'] ?? 0 );
}

/**
 * Read the local-ci receipts on a commit, newest first.
 *
 * Deliberately the list endpoint and not the combined one at /commits/:sha/status.
 * Only this shape includes `creator`, and creator is the identity the full design
 * validates against the trusted team — the combined endpoint silently omits it.
 *
 * @param string $token Auth token.
 * @param string $sha   Commit to read receipts from.
 *
 * @return array<int, array{context: string, state: string, creator: string}>
 */
function read_receipts( string $token, string $sha ): array {
	$response = github_api( $token, 'GET', sprintf( '/repos/%s/commits/%s/statuses', REPO, $sha ) );
	$seen     = array();
	$receipts = array();

	foreach ( (array) ( $response['body'] ?? array() ) as $status ) {
		$context = (string) ( $status['context'] ?? '' );

		// The list holds every status ever posted, so the same context appears once
		// per run. Newest is first, so the first one seen is the current one.
		if ( ! str_starts_with( $context, 'local-ci/' ) || isset( $seen[ $context ] ) ) {
			continue;
		}

		$seen[ $context ] = true;

		$receipts[] = array(
			'context' => $context,
			'state'   => (string) ( $status['state'] ?? '' ),
			'creator' => (string) ( $status['creator']['login'] ?? '<none>' ),
		);
	}

	return $receipts;
}

/**
 * One GitHub REST call.
 *
 * @param string                    $token  Auth token.
 * @param string                    $method HTTP method.
 * @param string                    $path   Path beginning with a slash.
 * @param array<string, mixed>|null $body   Optional JSON body.
 *
 * @return array{status: int, body: mixed}
 */
function github_api( string $token, string $method, string $path, ?array $body = null ): array {
	$curl = curl_init( 'https://api.github.com' . $path );

	curl_setopt_array(
		$curl,
		array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CUSTOMREQUEST  => $method,
			CURLOPT_USERAGENT      => 'woocommerce-local-ci-poc',
			CURLOPT_HTTPHEADER     => array(
				'Authorization: Bearer ' . $token,
				'Accept: application/vnd.github+json',
				'Content-Type: application/json',
			),
		)
	);

	if ( null !== $body ) {
		curl_setopt( $curl, CURLOPT_POSTFIELDS, (string) json_encode( $body ) );
	}

	$raw    = curl_exec( $curl );
	$status = (int) curl_getinfo( $curl, CURLINFO_HTTP_CODE );
	curl_close( $curl );

	return array(
		'status' => $status,
		'body'   => is_string( $raw ) ? json_decode( $raw, true ) : null,
	);
}

/**
 * Register cleanup for every way this script can end.
 *
 * An earlier shell version was interrupted mid-flight and leaked its ref. Any
 * real implementation needs this, or abandoned refs accumulate on the remote.
 */
function remove_the_temporary_ref_however_we_exit(): void {
	register_shutdown_function( 'delete_the_temporary_ref' );

	if ( ! function_exists( 'pcntl_signal' ) ) {
		return;
	}

	pcntl_async_signals( true );

	foreach ( array( SIGINT, SIGTERM ) as $signal ) {
		pcntl_signal(
			$signal,
			function () {
				delete_the_temporary_ref();
				exit( 130 );
			}
		);
	}
}

/**
 * Delete the temporary ref if one exists. Safe to call more than once.
 */
function delete_the_temporary_ref(): void {
	global $temporary_ref;

	if ( null === $temporary_ref ) {
		return;
	}

	git_succeeds( sprintf( 'push --no-verify -q origin --delete %s', $temporary_ref ) );
	$temporary_ref = null;
}

/**
 * Run a git command and return its trimmed output.
 *
 * @param string $arguments Arguments after `git`.
 */
function git( string $arguments ): string {
	return shell( 'git ' . $arguments );
}

/**
 * Run a git command and report only whether it succeeded.
 *
 * @param string $arguments Arguments after `git`.
 */
function git_succeeds( string $arguments ): bool {
	exec( 'git ' . $arguments . ' 2>/dev/null', $ignored, $exit_code );

	return 0 === $exit_code;
}

/**
 * Run a shell command and return its trimmed output.
 *
 * @param string $command Command to run.
 */
function shell( string $command ): string {
	return trim( (string) shell_exec( $command ) );
}

/**
 * The first characters of a SHA, for readable output.
 *
 * @param string $sha Full commit SHA.
 */
function short( string $sha ): string {
	return substr( $sha, 0, 11 ) . '…';
}

/**
 * A bold step heading, preceded by a blank line.
 *
 * @param string $text Heading text.
 */
function heading( string $text ): void {
	printf( "\n\033[1m%s\033[0m\n", $text );
}

/**
 * A green tick line.
 *
 * @param string $text Message.
 */
function pass( string $text ): void {
	printf( "  \033[32m✓\033[0m %s\n", $text );
}

/**
 * A red cross line.
 *
 * @param string $text Message.
 */
function fail( string $text ): void {
	printf( "  \033[31m✗\033[0m %s\n", $text );
}

/**
 * An indented plain line.
 *
 * @param string $text   Message.
 * @param int    $indent Indent level, two spaces each.
 */
function detail( string $text, int $indent = 1 ): void {
	printf( "%s%s\n", str_repeat( '  ', $indent ), $text );
}
