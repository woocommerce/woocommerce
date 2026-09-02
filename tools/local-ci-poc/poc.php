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
 *     php tools/local-ci-poc/poc.php            publish a receipt for HEAD
 *     php tools/local-ci-poc/poc.php --push     ... and then push the branch
 *
 * Requires the GitHub CLI, authenticated (`gh auth login`). The steps read top to
 * bottom; the plumbing they call is at the bottom of the file.
 */

const REPO = 'woocommerce/woocommerce';

/**
 * Receipts are named after the CI job they stand in for.
 *
 * `ci-jobs` already renames every test job to "<name> - <project> [<testType>]",
 * which identifies it uniquely, and ci.yml reads the same string from
 * `matrix.name`. Using it verbatim keeps the two sides in step with no parsing.
 */
const RECEIPT_PREFIX = 'local-ci/v1/';

/**
 * Which planned jobs this POC is willing to run locally.
 *
 * JavaScript unit tests only: they need no Docker, no database and no built
 * plugin, so a laptop runs them the same way CI does. PHP, e2e and performance
 * jobs are excluded because a local run would not be equivalent.
 */
const ELIGIBLE_TEST_TYPE = 'unit';
const ELIGIBLE_NAME_PREFIX = 'JavaScript';

/** Set once the temporary ref exists, so the shutdown handler knows to remove it. */
$temporary_ref = null;

/**
 * Whether to push the branch once the receipt is published.
 *
 * Off by default. Publishing a receipt is a local act with no visible effect,
 * but pushing a branch starts CI and notifies reviewers, so it stays something
 * the contributor asks for rather than something running the checks does to them.
 */
$push_when_done = false;

/**
 * How many checks to run at once, from --jobs.
 *
 * CI gives every job its own runner; a laptop has to share. Four is a deliberate
 * compromise: jest already spreads a single package's test files across its own
 * workers, so running many packages at once oversubscribes the CPU and each one
 * slows down. Raise it for a machine with cores to spare.
 */
$concurrency = 4;

/**
 * Substrings limiting which projects to run, from --only.
 *
 * Substitution is per job, so running a subset is legitimate: the jobs left out
 * simply get no receipt and CI runs them. This matters because the local run is
 * serial while CI's matrix is parallel — two of these packages take minutes on
 * their own, and a contributor may reasonably want the twenty quick ones only.
 *
 * @var string[]
 */
$only_projects = array();

foreach ( array_slice( $argv, 1 ) as $argument ) {
	if ( '--push' === $argument ) {
		$push_when_done = true;
		continue;
	}

	if ( str_starts_with( $argument, '--only=' ) ) {
		$only_projects = array_filter( array_map( 'trim', explode( ',', substr( $argument, 7 ) ) ) );
		continue;
	}

	if ( str_starts_with( $argument, '--jobs=' ) ) {
		$concurrency = max( 1, (int) substr( $argument, 7 ) );
		continue;
	}

	printf(
		"usage: php %s [--push] [--only=SUBSTRING[,...]] [--jobs=N]\n\n"
			. "  --push   push the current branch after the receipts are published, so the\n"
			. "           SHA that reaches GitHub is the one they name\n"
			. "  --only   only run jobs whose project name contains one of these substrings.\n"
			. "           Everything left out gets no receipt, so CI runs it as usual.\n"
			. "  --jobs   how many checks to run at once (default 4).\n",
		basename( __FILE__ )
	);

	exit( '--help' === $argument || '-h' === $argument ? 0 : 1 );
}

remove_the_temporary_ref_however_we_exit();

// ---------------------------------------------------------------------------
// 1. The GitHub CLI, which is the only supported way in.
// ---------------------------------------------------------------------------

heading( '1 · Require the GitHub CLI' );

$token = require_github_cli_token();

pass( 'gh is installed and authenticated' );

// ---------------------------------------------------------------------------
// 2. Refuse to publish anything the receipt could not honestly describe.
// ---------------------------------------------------------------------------

heading( '2 · Check this commit is publishable' );

// Captured before the check runs, so step 7 can confirm HEAD did not move
// underneath a long test run.
$sha = git( 'rev-parse HEAD' );

$modified  = modified_tracked_files();
$untracked = untracked_files();

if ( array() !== $modified ) {
	// The check runs against the working tree, but the receipt names HEAD. Let
	// those differ and the receipt vouches for code that was never tested.
	fail( sprintf( '%d tracked file(s) modified', count( $modified ) ) );
	warn( 'The check would run against the working tree while the receipt names' );
	warn( 'HEAD. Commit or stash first, so the two describe the same code.' );
	exit( 1 );
}

pass( 'no tracked file is modified — what gets tested is what HEAD contains' );

if ( array() !== $untracked ) {
	// Not a refusal. Scratch files are normal, and they are not part of HEAD, so
	// the receipt still describes what it claims to. Worth saying out loud
	// though: an untracked file can still change how a test behaves.
	warn( sprintf( '%d untracked file(s) present, e.g. %s', count( $untracked ), $untracked[0] ) );
	warn( 'They are not in HEAD, so the receipt is still accurate — but an' );
	warn( 'untracked file can change how a test behaves.' );
}

if ( ! fetch_trunk() ) {
	fail( 'could not fetch trunk — cannot tell whether this branch is current' );
	exit( 1 );
}

$behind = commits_behind_trunk();

if ( 0 !== $behind ) {
	// CI tests the merge of this branch with trunk, not this commit. Those trees
	// are identical only while the branch already contains the tip of trunk.
	fail( sprintf( 'branch is %d commit(s) behind trunk', $behind ) );
	warn( 'CI tests the merge of this branch with trunk, so a receipt published' );
	warn( 'now could vouch for a combination CI never tested. Merge trunk and' );
	warn( 're-run.' );
	exit( 1 );
}

pass( 'up to date with trunk — HEAD is the tree CI would merge and test' );

// ---------------------------------------------------------------------------
// 3. What CI would run for this diff, according to CI's own planner.
// ---------------------------------------------------------------------------

heading( '3 · Ask the planner what CI would run for this diff' );
detail( 'the same ci-jobs tool CI uses, run locally:' );

$eligible_jobs = eligible_jobs_for_this_diff();
$planned_count = count( $eligible_jobs );

if ( array() !== $only_projects ) {
	$eligible_jobs = array_values(
		array_filter(
			$eligible_jobs,
			function ( array $job ) use ( $only_projects ): bool {
				foreach ( $only_projects as $wanted ) {
					if ( str_contains( $job['projectName'], $wanted ) ) {
						return true;
					}
				}

				return false;
			}
		)
	);
}

if ( array() === $eligible_jobs ) {
	detail( 'no JavaScript unit jobs for this diff — nothing to substitute', 2 );
	warn( 'Change a JS package under packages/js to see this do something.' );
	exit( 0 );
}

foreach ( $eligible_jobs as $job ) {
	detail( $job['name'], 2 );
}

detail( sprintf( '%d job(s) this POC can run locally', count( $eligible_jobs ) ), 2 );

if ( count( $eligible_jobs ) < $planned_count ) {
	detail(
		sprintf( '--only kept %d of %d; CI runs the rest', count( $eligible_jobs ), $planned_count ),
		2
	);
} elseif ( $planned_count > 5 ) {
	// CI gives each of these its own runner. Even run concurrently here they
	// share one machine, and two of the bigger packages take minutes alone.
	warn( 'CI gives each of these its own runner; here they share this machine.' );
	warn( 'Use --only=<substring> to substitute a subset, or --jobs=N to widen.' );
}

// ---------------------------------------------------------------------------
// 4. Run one of those checks for real.
// ---------------------------------------------------------------------------

heading( '4 · Run those checks locally' );

detail( sprintf( 'up to %d at a time; results appear as each finishes', $concurrency ), 2 );

$started_all = time();
$passed_jobs = array();
$failed_jobs = array();

run_checks_in_parallel(
	$eligible_jobs,
	$concurrency,
	function ( array $job, array $check ) use ( &$passed_jobs, &$failed_jobs ): void {
		// Keep the duration on the record so the summary below can compare the
		// work done against the time it actually took.
		$job['seconds'] = $check['seconds'];

		if ( $check['passed'] ) {
			$passed_jobs[] = $job;
			pass( sprintf( '%s in %ds — %s', $job['projectName'], $check['seconds'], $check['summary'] ) );
			return;
		}

		// Substitution is per job, so one failure costs only its own job. That job
		// gets no receipt, CI runs it, and CI decides. Nothing here can turn a
		// failing check into a skipped one.
		$failed_jobs[] = $job;
		fail( sprintf( '%s — no receipt, CI will run this one', $job['projectName'] ) );
	}
);

$wall_clock = time() - $started_all;
$cpu_time   = array_sum( array_column( array_merge( $passed_jobs, $failed_jobs ), 'seconds' ) );

detail(
	sprintf( '%d passed, %d failed, in %ds', count( $passed_jobs ), count( $failed_jobs ), $wall_clock ),
	2
);

if ( $cpu_time > $wall_clock && $wall_clock > 0 ) {
	detail( sprintf( '%ds of work done in %ds wall clock', $cpu_time, $wall_clock ), 2 );
}

if ( array() === $passed_jobs ) {
	fail( 'nothing passed — there is no receipt to publish' );
	exit( 1 );
}

if ( array() !== $failed_jobs ) {
	warn( 'Some checks failed. Their jobs will run in CI and may fail there too.' );
	warn( 'A failing check locally is not skipped — it is simply left to CI.' );
}

// ---------------------------------------------------------------------------
// 5. Make the commit known to GitHub without pushing a branch.
// ---------------------------------------------------------------------------

heading( '5 · Publish the commit to a ref that triggers nothing' );

detail( 'before: does GitHub know this commit?' );
$known_before = commit_is_known_to_github( $token, $sha );
detail( sprintf( 'GET /commits/%s → HTTP %d', short( $sha ), $known_before ), 2 );

if ( 200 === $known_before ) {
	warn( 'NOTE: this commit is already on the remote (pushed, or open in a PR), so' );
	warn( '      the 422 → 200 transition cannot be shown. To see it, make a local' );
	warn( '      commit and run this before pushing. Everything below still runs.' );
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
// 6. Confirm that ref started nothing.
// ---------------------------------------------------------------------------

heading( '6 · Confirm no workflow was triggered' );

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
// 7. Publish the receipt.
// ---------------------------------------------------------------------------

heading( '7 · Publish the receipt' );

if ( git( 'rev-parse HEAD' ) !== $sha ) {
	// A commit landed while the check was running, so the result describes a tree
	// that is no longer HEAD.
	fail( 'HEAD moved while the check was running — publishing nothing' );
	exit( 1 );
}

foreach ( $passed_jobs as $job ) {
	$posted = github_api(
		$token,
		'POST',
		sprintf( '/repos/%s/statuses/%s', REPO, $sha ),
		array(
			'state'       => 'success',
			'context'     => RECEIPT_PREFIX . $job['name'],
			'description' => 'passed locally',
		)
	);

	if ( 201 !== $posted['status'] ) {
		// Carrying on would make a failed publish look like a successful one, and
		// CI would then run a job the contributor believes was covered.
		fail( sprintf( 'receipt for %s not published (HTTP %d)', $job['projectName'], $posted['status'] ) );

		$message = (string) ( $posted['body']['message'] ?? '' );

		if ( '' !== $message ) {
			warn( 'GitHub said: ' . $message );
		}

		if ( 403 === $posted['status'] ) {
			warn( 'A 403 here usually means the token lacks the repo:status scope.' );
		}

		exit( 1 );
	}
}

pass( sprintf( '%d receipt(s) published', count( $passed_jobs ) ) );

// ---------------------------------------------------------------------------
// 8. Read it back the way the workflow does.
// ---------------------------------------------------------------------------

heading( '8 · Read it back, as CI would' );

$receipts = read_receipts( $token, $sha );

foreach ( array_slice( $receipts, 0, 3 ) as $receipt ) {
	detail(
		sprintf( '%s  %s  creator=%s', $receipt['context'], $receipt['state'], $receipt['creator'] ),
		2
	);
}

if ( count( $receipts ) > 3 ) {
	detail( sprintf( '... and %d more', count( $receipts ) - 3 ), 2 );
}

detail( 'ci.yml reads exactly this, per matrix job, and skips both the install and', 2 );
detail( 'the test run when it finds a success for that job.', 2 );
warn( 'Trust is NOT implemented: the workflow does not yet check that creator' );
warn( 'belongs to a trusted team. That is the next piece.' );

// ---------------------------------------------------------------------------
// 9. Optionally push, while the receipt still describes HEAD.
// ---------------------------------------------------------------------------

heading( '9 · Push the branch' );

if ( ! $push_when_done ) {
	detail( 'not pushing (no --push)', 2 );
	warn( 'The receipt names ' . short( $sha ) . '. Commit anything more before you' );
	warn( 'push and the pushed SHA has no receipt, so CI runs everything.' );
	warn( 'Re-run with --push to close that window.' );
} elseif ( push_the_current_branch() ) {
	pass( sprintf( 'pushed %s — the SHA CI sees is the one the receipt names', branch_name() ) );
} else {
	// Not fatal: the receipt is already published and stays valid for this SHA.
	fail( 'the push failed' );
	warn( 'The receipt is published and still valid. Push when ready, as long as' );
	warn( 'HEAD is still ' . short( $sha ) . '.' );
	exit( 1 );
}

// ---------------------------------------------------------------------------
// 10. Leave nothing behind but the receipt.
// ---------------------------------------------------------------------------

heading( '10 · Clean up' );

delete_the_temporary_ref();
pass( 'temporary ref removed' );
detail( 'the status remains on the commit — that is the receipt', 2 );

exit( 0 );


// ===========================================================================
// Plumbing.
// ===========================================================================

/**
 * Get a token from the GitHub CLI, or stop.
 *
 * The CLI is the single supported source. Reading tokens out of the environment
 * or the git credential store as well would mean this script could authenticate
 * as one identity while `gh` reports another, and the receipt's creator is the
 * whole basis for trusting it. One source keeps that unambiguous.
 *
 * `gh auth token` honours GH_TOKEN and GITHUB_TOKEN itself, so exporting either
 * still works — it just goes through the CLI rather than around it.
 */
function require_github_cli_token(): string {
	if ( '' === shell( 'command -v gh 2>/dev/null' ) ) {
		fail( 'the GitHub CLI (gh) is not installed' );
		warn( 'This script requires it. Install from https://cli.github.com, then' );
		warn( 'run `gh auth login`.' );
		exit( 1 );
	}

	$token = shell( 'gh auth token 2>/dev/null' );

	if ( '' === $token ) {
		fail( 'the GitHub CLI is installed but not authenticated' );
		warn( 'Run `gh auth login`, then try this again.' );
		exit( 1 );
	}

	return $token;
}

/**
 * Ask the monorepo's own CI planner which jobs this diff would produce, and keep
 * the ones this POC can honestly run on a laptop.
 *
 * The planner only emits the full job matrix when it believes it is running in
 * Actions, so it is run that way deliberately, with GITHUB_OUTPUT pointed at a
 * temporary file. That yields the same JSON CI feeds into `matrix`, including
 * each job's real command — parsing the human-readable listing instead would
 * mean guessing at commands.
 *
 * @return array<int, array{name: string, projectName: string, command: string}>
 */
function eligible_jobs_for_this_diff(): array {
	$output_file = sys_get_temp_dir() . '/poc-ci-jobs-' . getmypid() . '.txt';
	touch( $output_file );

	shell(
		sprintf(
			'GITHUB_ACTIONS=true GITHUB_OUTPUT=%s pnpm utils ci-jobs --base-ref trunk 2>/dev/null',
			escapeshellarg( $output_file )
		)
	);

	$raw = (string) file_get_contents( $output_file );
	unlink( $output_file );

	// Actions writes multi-line outputs as `name<<DELIMITER ... DELIMITER`.
	if ( ! preg_match( '/test-jobs<<(\S+)\n(.*?)\n\1/s', $raw, $matches ) ) {
		return array();
	}

	$planned  = json_decode( $matches[2], true );
	$eligible = array();

	foreach ( (array) $planned as $job ) {
		$name = (string) ( $job['name'] ?? '' );

		if ( ELIGIBLE_TEST_TYPE !== ( $job['testType'] ?? '' ) || ! str_starts_with( $name, ELIGIBLE_NAME_PREFIX ) ) {
			continue;
		}

		$eligible[] = array(
			'name'        => $name,
			'projectName' => (string) ( $job['projectName'] ?? '' ),
			'command'     => (string) ( $job['command'] ?? '' ),
		);
	}

	return $eligible;
}

/**
 * Run planned jobs, several at a time, reporting each as it finishes.
 *
 * CI runs this matrix across separate runners; the nearest a laptop gets is a
 * pool. Jobs are started in order but finish in whatever order they finish, so
 * results are reported through the callback rather than returned as a batch —
 * a fifteen-second package should not sit behind a three-minute one before the
 * reader hears about it.
 *
 * @param array<int, array{name: string, projectName: string, command: string}> $jobs        Jobs to run.
 * @param int                                                                   $concurrency How many at once.
 * @param callable                                                              $on_finish   Called with (job, check result).
 */
function run_checks_in_parallel( array $jobs, int $concurrency, callable $on_finish ): void {
	$queue   = $jobs;
	$running = array();

	while ( array() !== $queue || array() !== $running ) {
		while ( array() !== $queue && count( $running ) < $concurrency ) {
			$running[] = start_the_check( array_shift( $queue ) );
		}

		foreach ( $running as $index => $process ) {
			if ( proc_get_status( $process['handle'] )['running'] ) {
				continue;
			}

			$on_finish( $process['job'], finish_the_check( $process ) );
			unset( $running[ $index ] );
		}

		// Nothing to do but wait for a child to exit; polling any faster would
		// just spin the CPU that the checks themselves need.
		usleep( 200000 );
	}
}

/**
 * Start one planned job exactly as CI would.
 *
 * The command comes from the planner rather than being assumed, so this cannot
 * drift from what CI runs for the same job. Output goes straight to a per-job
 * file: with several running at once, interleaving them on this terminal would
 * make all of them unreadable.
 *
 * @param array{name: string, projectName: string, command: string} $job Job to start.
 *
 * @return array{job: array, handle: resource, log: string, started: int}
 */
function start_the_check( array $job ): array {
	$log = sprintf( '%s/poc-check-%s.log', sys_get_temp_dir(), md5( $job['name'] ) );

	$descriptors = array(
		0 => array( 'file', '/dev/null', 'r' ),
		1 => array( 'file', $log, 'w' ),
		2 => array( 'file', $log, 'a' ),
	);

	$command = sprintf(
		'pnpm --filter=%s %s',
		escapeshellarg( $job['projectName'] ),
		$job['command']
	);

	return array(
		'job'     => $job,
		'handle'  => proc_open( $command, $descriptors, $pipes ),
		'log'     => $log,
		'started' => time(),
	);
}

/**
 * Collect the result of a finished job.
 *
 * @param array{job: array, handle: resource, log: string, started: int} $process Finished process.
 *
 * @return array{passed: bool, seconds: int, summary: string}
 */
function finish_the_check( array $process ): array {
	$exit_code = proc_close( $process['handle'] );
	$output    = is_readable( $process['log'] ) ? (string) file_get_contents( $process['log'] ) : '';
	$summary   = preg_match( '/Tests: +\d+ passed/', $output, $matches ) ? $matches[0] : 'ran';

	return array(
		'passed'  => 0 === $exit_code,
		'seconds' => time() - $process['started'],
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
 * Push the current branch to origin.
 *
 * Deliberately without --no-verify, unlike the temporary ref: this is a real
 * branch push, so the repo's own pre-push hook should run exactly as it would
 * for a hand-typed `git push`.
 */
function push_the_current_branch(): bool {
	return git_succeeds( 'push origin HEAD' );
}

/**
 * The current branch name, for output.
 */
function branch_name(): string {
	return git( 'branch --show-current' );
}

/**
 * Tracked files with staged or unstaged changes.
 *
 * These are what make the working tree differ from HEAD, so they are what makes
 * a receipt naming HEAD dishonest.
 *
 * @return string[] Paths.
 */
function modified_tracked_files(): array {
	return status_paths( false );
}

/**
 * Files git does not track. Respects .gitignore, so build output does not count.
 *
 * @return string[] Paths.
 */
function untracked_files(): array {
	return status_paths( true );
}

/**
 * Read `git status --porcelain` and return one side of it.
 *
 * @param bool $want_untracked True for untracked paths, false for tracked changes.
 *
 * @return string[] Paths.
 */
function status_paths( bool $want_untracked ): array {
	$paths = array();

	foreach ( explode( "\n", git( 'status --porcelain' ) ) as $line ) {
		if ( '' === trim( $line ) ) {
			continue;
		}

		$is_untracked = str_starts_with( $line, '?? ' );

		if ( $is_untracked === $want_untracked ) {
			$paths[] = trim( substr( $line, 3 ) );
		}
	}

	return $paths;
}

/**
 * Fetch trunk from the remote so the staleness check reads current data.
 *
 * A stale local ref would happily report "up to date" against a trunk that moved
 * days ago, which is the exact thing the check exists to catch.
 */
function fetch_trunk(): bool {
	return git_succeeds( 'fetch -q origin trunk' );
}

/**
 * How many commits trunk has that this branch does not.
 *
 * Reads FETCH_HEAD rather than origin/trunk, because that is what `git fetch
 * origin trunk` is guaranteed to have just written.
 */
function commits_behind_trunk(): int {
	return (int) git( 'rev-list --count HEAD..FETCH_HEAD' );
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
	printf( "\n%s\n", paint( $text, 'bold' ) );
}

/**
 * A green line: something held.
 *
 * @param string $text Message.
 */
function pass( string $text ): void {
	printf( "  %s\n", paint( '✓ ' . $text, 'green' ) );
}

/**
 * A red line: something did not hold, and the run stops.
 *
 * @param string $text Message.
 */
function fail( string $text ): void {
	printf( "  %s\n", paint( '✗ ' . $text, 'red' ) );
}

/**
 * A yellow line: something the reader should notice but which is not a failure.
 *
 * Used for the guidance printed after a refusal, and for the caveats that say
 * what this POC has not implemented.
 *
 * @param string $text   Message.
 * @param int    $indent Indent level, two spaces each.
 */
function warn( string $text, int $indent = 2 ): void {
	printf( "%s%s\n", str_repeat( '  ', $indent ), paint( $text, 'yellow' ) );
}

/**
 * Whether to emit colour at all.
 *
 * Off when the output is not a terminal, so piping a run into a file or pasting
 * it into a ticket gives clean text rather than escape codes, and off when
 * NO_COLOR is set (https://no-color.org).
 */
function colour_is_available(): bool {
	static $available = null;

	if ( null === $available ) {
		$available = false === getenv( 'NO_COLOR' ) && stream_isatty( STDOUT );
	}

	return $available;
}

/**
 * Wrap text in an ANSI colour, or return it untouched when colour is off.
 *
 * @param string $text   Text to colour.
 * @param string $colour One of bold, red, green, yellow.
 */
function paint( string $text, string $colour ): string {
	$codes = array(
		'bold'   => '1',
		'red'    => '31',
		'green'  => '32',
		'yellow' => '33',
	);

	if ( ! colour_is_available() || ! isset( $codes[ $colour ] ) ) {
		return $text;
	}

	return sprintf( "\033[%sm%s\033[0m", $codes[ $colour ], $text );
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

// Testing 2
