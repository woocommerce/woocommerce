#!/usr/bin/env php
<?php
/**
 * Proof of concept for local-first CI receipts.
 *
 * Run the checks CI would run, on this machine, and leave a note on the commit
 * saying they passed. CI reads that note and skips those jobs, which frees the
 * runner slots they would have taken.
 *
 * The one part that is not obvious: a commit status can be attached to a commit
 * GitHub has never seen, before the branch that would trigger CI is pushed. That
 * ordering is what removes the race — publishing after pushing is too late,
 * because the workflow has already started.
 *
 *     php tools/local-ci-poc/poc.php --help
 *
 * Requires the GitHub CLI, authenticated (`gh auth login`).
 *
 * This file is the narrative: it reads as the steps in order. Everything it
 * calls lives in lib/, one concern per file. See README.md for which of those
 * are repository-agnostic and which would need replacing elsewhere.
 */

namespace LocalCi;

require_once __DIR__ . '/lib/Shell.php';
require_once __DIR__ . '/lib/Cleanup.php';
require_once __DIR__ . '/lib/Output.php';
require_once __DIR__ . '/lib/Git.php';
require_once __DIR__ . '/lib/GitHubApi.php';
require_once __DIR__ . '/lib/Receipts.php';
require_once __DIR__ . '/lib/LogStore.php';
require_once __DIR__ . '/lib/CheckRunner.php';
require_once __DIR__ . '/lib/JobPlanner.php';
require_once __DIR__ . '/lib/TemporaryRef.php';
require_once __DIR__ . '/lib/Options.php';

const REPOSITORY   = 'woocommerce/woocommerce';
const TRUNK_BRANCH = 'trunk';

// Armed before anything is started, so an interrupt during the checks — by far
// the longest part of a run — stops them rather than orphaning them.
Cleanup::arm();
Cleanup::add( array( CheckRunner::class, 'stop_everything' ) );
Cleanup::add( array( TemporaryRef::class, 'remove' ) );

$options = Options::from_argv( $argv );

if ( $options->wants_help || null !== $options->unknown_argument ) {
	Output::usage( Options::usage( basename( __FILE__ ) ) );
	exit( $options->wants_help ? 0 : 1 );
}

// ---------------------------------------------------------------------------
// 1. The GitHub CLI, which is the only supported way in.
// ---------------------------------------------------------------------------

Output::heading( '1 · Require the GitHub CLI' );

$github = GitHubApi::from_github_cli( REPOSITORY );

if ( null === $github ) {
	if ( GitHubApi::github_cli_is_installed() ) {
		Output::fail( 'the GitHub CLI is installed but not authenticated' );
		Output::warn( 'Run `gh auth login`, then try this again.' );
	} else {
		Output::fail( 'the GitHub CLI (gh) is not installed' );
		Output::warn( 'This tool requires it. Install from https://cli.github.com,' );
		Output::warn( 'then run `gh auth login`.' );
	}

	exit( 1 );
}

$receipts = new Receipts( $github );

Output::pass( 'gh is installed and authenticated' );

// ---------------------------------------------------------------------------
// 2. Refuse to publish anything a receipt could not honestly describe.
// ---------------------------------------------------------------------------

Output::heading( '2 · Check this commit is publishable' );

// Captured before the checks run, so step 7 can confirm HEAD did not move
// underneath a long run.
$sha = Git::head_sha();

$modified = Git::modified_tracked_files();

if ( array() !== $modified ) {
	// The checks run against the working tree, but the receipt names HEAD. Let
	// those differ and the receipt vouches for code that was never tested.
	Output::fail( sprintf( '%d tracked file(s) modified', count( $modified ) ) );
	Output::warn( 'The checks would run against the working tree while the receipts' );
	Output::warn( 'name HEAD. Commit or stash first, so the two describe the same code.' );
	exit( 1 );
}

Output::pass( 'no tracked file is modified — what gets tested is what HEAD contains' );

$untracked = Git::untracked_files();

if ( array() !== $untracked ) {
	// Not a refusal. Scratch files are normal and are not part of HEAD, so the
	// receipts still describe what they claim to. Worth saying out loud though:
	// an untracked file can still change how a test behaves.
	Output::warn( sprintf( '%d untracked file(s) present, e.g. %s', count( $untracked ), $untracked[0] ) );
	Output::warn( 'They are not in HEAD, so the receipts stay accurate — but an' );
	Output::warn( 'untracked file can change how a test behaves.' );
}

if ( ! Git::fetch_trunk( TRUNK_BRANCH ) ) {
	Output::fail( 'could not fetch trunk — cannot tell whether this branch is current' );
	exit( 1 );
}

$behind = Git::commits_behind_trunk();

if ( 0 !== $behind ) {
	// CI tests the merge of this branch with trunk, not this commit. Those trees
	// are identical only while the branch already contains the tip of trunk.
	Output::fail( sprintf( 'branch is %d commit(s) behind trunk', $behind ) );
	Output::warn( 'CI tests the merge of this branch with trunk, so a receipt published' );
	Output::warn( 'now could vouch for a combination CI never tested. Merge trunk and' );
	Output::warn( 're-run.' );
	exit( 1 );
}

Output::pass( 'up to date with trunk — HEAD is the tree CI would merge and test' );

// ---------------------------------------------------------------------------
// 3. Ask CI's own planner what this diff would run.
// ---------------------------------------------------------------------------

Output::heading( '3 · Ask the planner what CI would run for this diff' );
Output::detail( 'the same ci-jobs tool CI uses, run locally:' );

$planner       = new JobPlanner( Git::fetched_trunk_sha() );
$eligible_jobs = $planner->eligible_jobs();
$planned_count = count( $eligible_jobs );
$eligible_jobs = JobPlanner::only( $eligible_jobs, $options->only );

if ( array() === $eligible_jobs ) {
	Output::detail( 'no JavaScript unit jobs for this diff — nothing to substitute', 2 );
	Output::warn( 'Change a JS package under packages/js to see this do something.' );
	exit( 0 );
}

foreach ( $eligible_jobs as $job ) {
	Output::detail( $job['name'], 2 );
}

Output::detail( sprintf( '%d job(s) this tool can run locally', count( $eligible_jobs ) ), 2 );

if ( count( $eligible_jobs ) < $planned_count ) {
	Output::detail(
		sprintf( '--only kept %d of %d; CI runs the rest', count( $eligible_jobs ), $planned_count ),
		2
	);
} elseif ( $planned_count > 5 ) {
	// CI gives each of these its own runner. Even run concurrently here they
	// share one machine, and a couple of the bigger packages take minutes alone.
	Output::warn( 'CI gives each of these its own runner; here they share this machine.' );
	Output::warn( 'Use --only=<substring> to substitute a subset, or --jobs=N to widen.' );
}

// ---------------------------------------------------------------------------
// 4. Run them.
// ---------------------------------------------------------------------------

Output::heading( '4 · Run those checks locally' );
Output::detail( sprintf( 'up to %d at a time; results appear as each finishes', $options->concurrency ), 2 );

$started_all = time();
$passed_jobs = array();
$failed_jobs = array();
$logs        = array();

( new CheckRunner() )->run(
	$eligible_jobs,
	$options->concurrency,
	static function ( array $job, array $result ) use ( &$passed_jobs, &$failed_jobs, &$logs ): void {
		// Kept so a reviewer can read what actually ran, not just that it passed.
		$logs[ $job['name'] ] = $result['output'];

		// Keep the duration on the record so the summary can compare the work
		// done against the time it actually took.
		$job['seconds'] = $result['seconds'];

		if ( $result['passed'] ) {
			$passed_jobs[] = $job;
			Output::pass( sprintf( '%s in %ds — %s', $job['projectName'], $result['seconds'], $result['summary'] ) );

			return;
		}

		// Substitution is per job, so one failure costs only its own job. That
		// job gets no receipt, CI runs it, and CI decides. Nothing here can turn
		// a failing check into a skipped one.
		$failed_jobs[] = $job;
		Output::fail( sprintf( '%s — no receipt, CI will run this one', $job['projectName'] ) );
	}
);

$wall_clock = time() - $started_all;
$cpu_time   = array_sum( array_column( array_merge( $passed_jobs, $failed_jobs ), 'seconds' ) );

Output::detail(
	sprintf( '%d passed, %d failed, in %ds', count( $passed_jobs ), count( $failed_jobs ), $wall_clock ),
	2
);

if ( $cpu_time > $wall_clock && $wall_clock > 0 ) {
	Output::detail( sprintf( '%ds of work done in %ds wall clock', $cpu_time, $wall_clock ), 2 );
}

if ( array() === $passed_jobs ) {
	Output::fail( 'nothing passed — there is no receipt to publish' );
	exit( 1 );
}

if ( array() !== $failed_jobs ) {
	Output::warn( 'Some checks failed. Their jobs will run in CI and may fail there too.' );
	Output::warn( 'A failing check locally is not skipped — it is simply left to CI.' );
}

// ---------------------------------------------------------------------------
// 5. Make the commit known to GitHub without pushing a branch.
// ---------------------------------------------------------------------------

Output::heading( '5 · Publish the commit to a ref that triggers nothing' );

Output::detail( 'before: does GitHub know this commit?' );
$known_before = $github->commit_status_code( $sha );
Output::detail( sprintf( 'GET /commits/%s → HTTP %d', Output::short_sha( $sha ), $known_before ), 2 );

if ( 200 === $known_before ) {
	Output::warn( 'NOTE: this commit is already on the remote (pushed, or open in a PR),' );
	Output::warn( '      so the 422 → 200 transition cannot be shown. To see it, make a' );
	Output::warn( '      local commit and run this before pushing. The rest still runs.' );
}

// Counted before the push so the next step measures what this push caused,
// rather than runs the branch or its PR had already produced.
$runs_before = $github->workflow_run_count( $sha );

if ( ! TemporaryRef::publish( $sha ) ) {
	Output::fail( 'could not push the temporary ref' );
	exit( 1 );
}

Output::pass( 'pushed to ' . TemporaryRef::name() );

Output::detail( 'after:' );
Output::detail(
	sprintf( 'GET /commits/%s → HTTP %d', Output::short_sha( $sha ), $github->commit_status_code( $sha ) ),
	2
);

// ---------------------------------------------------------------------------
// 6. Confirm that ref started nothing.
// ---------------------------------------------------------------------------

Output::heading( '6 · Confirm no workflow was triggered' );

sleep( 4 );
$runs_after = $github->workflow_run_count( $sha );

Output::detail( sprintf( 'workflow runs for this SHA: %d before the push, %d after', $runs_before, $runs_after ), 2 );

if ( $runs_before === $runs_after ) {
	Output::pass( 'the temporary ref triggered nothing' );

	if ( 0 !== $runs_after ) {
		Output::detail( sprintf( '(the %d existing runs come from the branch push and PR, not this ref)', $runs_after ), 2 );
	}
} else {
	Output::fail( 'count changed — the temporary ref triggered something; investigate' );
}

Output::detail( 'refs/local-ci/* is outside refs/heads/* and refs/tags/*, so Actions cannot trigger on it', 2 );

// ---------------------------------------------------------------------------
// 7. Publish one receipt per job that passed.
// ---------------------------------------------------------------------------

Output::heading( '7 · Publish the receipts' );

if ( Git::head_sha() !== $sha ) {
	// A commit landed while the checks were running, so the results describe a
	// tree that is no longer HEAD.
	Output::fail( 'HEAD moved while the checks were running — publishing nothing' );
	exit( 1 );
}

// Trunk moving has the same effect as HEAD moving, just from the other side: CI
// merges this branch with whatever trunk is at the time, so commits that landed
// during the run put CI on a tree these checks never saw. Step 2 established
// this at the start; a long run can outlive that.
if ( ! Git::fetch_trunk( TRUNK_BRANCH ) ) {
	Output::fail( 'could not re-check trunk — publishing nothing' );
	exit( 1 );
}

$drifted = Git::commits_behind_trunk();

if ( 0 !== $drifted ) {
	Output::fail( sprintf( 'trunk moved %d commit(s) while the checks were running', $drifted ) );
	Output::warn( 'CI would merge those commits and build a tree these checks never saw.' );
	Output::warn( 'Merge trunk and run again. A long run loses this race more often, so' );
	Output::warn( '--only=<substring> is worth using on a busy trunk.' );
	exit( 1 );
}

$log_urls = ( new LogStore( sprintf( 'local-ci run for %s', substr( $sha, 0, 11 ) ) ) )->upload(
	array_intersect_key( $logs, array_flip( array_column( $passed_jobs, 'name' ) ) )
);

if ( array() === $log_urls ) {
	// Not fatal. A receipt without a link still substitutes the job; it just
	// gives a reviewer nothing to inspect.
	Output::warn( 'could not upload the run output — receipts will have no link' );
} else {
	Output::detail( sprintf( 'output uploaded: %s', strtok( (string) reset( $log_urls ), '#' ) ), 2 );
}

foreach ( $passed_jobs as $job ) {
	$posted = $receipts->publish( $sha, $job, $log_urls[ $job['name'] ] ?? '' );

	if ( 201 !== $posted['status'] ) {
		// Carrying on would make a failed publish look like a successful one, and
		// CI would then run a job the contributor believes was covered.
		Output::fail( sprintf( 'receipt for %s not published (HTTP %d)', $job['projectName'], $posted['status'] ) );

		$message = (string) ( $posted['body']['message'] ?? '' );

		if ( '' !== $message ) {
			Output::warn( 'GitHub said: ' . $message );
		}

		if ( 403 === $posted['status'] ) {
			Output::warn( 'A 403 here usually means the token lacks the repo:status scope.' );
		}

		exit( 1 );
	}
}

Output::pass( sprintf( '%d receipt(s) published', count( $passed_jobs ) ) );

// ---------------------------------------------------------------------------
// 8. Read them back the way CI does.
// ---------------------------------------------------------------------------

Output::heading( '8 · Read them back, as CI would' );

$published = $receipts->read( $sha );

foreach ( array_slice( $published, 0, 3 ) as $receipt ) {
	Output::detail(
		sprintf( '%s  %s  creator=%s', $receipt['context'], $receipt['state'], $receipt['creator'] ),
		2
	);
}

if ( count( $published ) > 3 ) {
	Output::detail( sprintf( '... and %d more', count( $published ) - 3 ), 2 );
}

Output::detail( 'ci.yml reads exactly this, per matrix job, and skips both the install and', 2 );
Output::detail( 'the test run when it finds a success for that job.', 2 );
Output::warn( 'Trust is NOT implemented: the workflow does not yet check that creator' );
Output::warn( 'belongs to a trusted team. That is the next piece.' );

// ---------------------------------------------------------------------------
// 9. Optionally push, while the receipts still describe HEAD.
// ---------------------------------------------------------------------------

Output::heading( '9 · Push the branch' );

if ( ! $options->push ) {
	Output::detail( 'not pushing (no --push)', 2 );
	Output::warn( 'The receipts name ' . Output::short_sha( $sha ) . '. Commit anything more' );
	Output::warn( 'before you push and the pushed SHA has no receipts, so CI runs' );
	Output::warn( 'everything. Re-run with --push to close that window.' );
} elseif ( Git::push_current_branch() ) {
	Output::pass( sprintf( 'pushed %s — the SHA CI sees is the one the receipts name', Git::branch_name() ) );
} else {
	// Not fatal: the receipts are published and stay valid for this SHA.
	Output::fail( 'the push failed' );
	Output::warn( 'The receipts are published and still valid. Push when ready, as long' );
	Output::warn( 'as HEAD is still ' . Output::short_sha( $sha ) . '.' );
	exit( 1 );
}

// ---------------------------------------------------------------------------
// 10. Leave nothing behind but the receipts.
// ---------------------------------------------------------------------------

Output::heading( '10 · Clean up' );

TemporaryRef::remove();
Output::pass( 'temporary ref removed' );
Output::detail( 'the statuses remain on the commit — those are the receipts', 2 );

exit( 0 );
