/**
 * CI Queue Sentinel watchdog: cancels sentinel runs that have sat queued or
 * in progress for longer than STALE_AFTER_MIN. A run whose job never reaches
 * a runner never starts, so the job's timeout-minutes never fires, and it
 * holds the sentinel's concurrency group until someone cancels it by hand.
 * DRY_RUN=1 reports without cancelling.
 */

const fs = require( 'node:fs' );

const {
	REPOSITORY,
	GITHUB_TOKEN,
	GITHUB_RUN_ID,
	STALE_AFTER_MIN = '12',
	DRY_RUN,
	CI_QUEUE_OVERFLOW,
	GITHUB_STEP_SUMMARY,
	GITHUB_OUTPUT,
} = process.env;

const WORKFLOW_FILE = 'ci-queue-sentinel.yml';
const API_BASE = 'https://api.github.com';
const MAX_RUN_LIST_PAGES = 5;
const MAX_FETCH_RETRIES = 2;

const sleep = ( seconds ) => new Promise( ( resolve ) => setTimeout( resolve, seconds * 1000 ) );

const ghFetch = async ( url, { token, method = 'GET', body } = {} ) => {
	for ( let attempt = 0; ; attempt++ ) {
		let response;
		try {
			response = await fetch( url, {
				method,
				headers: {
					'User-Agent': 'node.js',
					Authorization: `Bearer ${ token }`,
					Accept: 'application/vnd.github+json',
				},
				body: body ? JSON.stringify( body ) : undefined,
				signal: AbortSignal.timeout( 30000 ),
			} );
		} catch ( error ) {
			// Network-level failures are retryable too.
			if ( attempt < MAX_FETCH_RETRIES ) {
				await sleep( ( attempt + 1 ) * 15 );
				continue;
			}
			throw error;
		}
		const retryable = response.status === 403 || response.status === 429 || response.status >= 500;
		if ( retryable && attempt < MAX_FETCH_RETRIES ) {
			const retryAfter = Number( response.headers.get( 'retry-after' ) ) || ( attempt + 1 ) * 15;
			await sleep( Math.min( retryAfter, 60 ) );
			continue;
		}
		return response;
	}
};

const ghJson = async ( url, options ) => {
	const response = await ghFetch( url, options );
	if ( ! response.ok ) {
		throw new Error( `${ options?.method || 'GET' } ${ url } -> HTTP ${ response.status }` );
	}
	return response.json();
};

// Lists this workflow's queued and in_progress runs, newest first. The list
// is short (the sentinel runs one tick at a time) so pagination only matters
// after a long outage; the page cap keeps a pathological backlog bounded.
const fetchActiveRuns = async () => {
	const byId = new Map();
	for ( const status of [ 'queued', 'in_progress' ] ) {
		for ( let page = 1; page <= MAX_RUN_LIST_PAGES; page++ ) {
			const data = await ghJson(
				`${ API_BASE }/repos/${ REPOSITORY }/actions/workflows/${ WORKFLOW_FILE }/runs?status=${ status }&per_page=100&page=${ page }`,
				{ token: GITHUB_TOKEN }
			);
			const runs = data.workflow_runs || [];
			for ( const run of runs ) {
				byId.set( run.id, run );
			}
			if ( runs.length < 100 ) {
				break;
			}
		}
	}
	return [ ...byId.values() ];
};

/**
 * Picks the runs that have been active for longer than the stale window.
 *
 * Age is measured from run_started_at so a re-run of an old run is judged by
 * its latest attempt, with created_at as the fallback. The current run is
 * excluded so the watchdog never cancels the run it belongs to.
 *
 * @param {Object[]}      runs         Runs from the workflow runs API.
 * @param {number}        nowMs
 * @param {number}        staleAfterMin
 * @param {string|number} currentRunId
 * @return {Object[]} Stale runs, oldest first: { id, status, ageMin, html_url }.
 */
const findStaleRuns = ( runs, nowMs, staleAfterMin, currentRunId ) =>
	runs
		.filter( ( run ) => String( run.id ) !== String( currentRunId ) )
		.filter( ( run ) => run.status === 'queued' || run.status === 'in_progress' )
		.map( ( run ) => ( {
			id: run.id,
			status: run.status,
			html_url: run.html_url,
			ageMin: ( nowMs - new Date( run.run_started_at || run.created_at ).getTime() ) / 60000,
		} ) )
		.filter( ( run ) => run.ageMin > staleAfterMin )
		.sort( ( a, b ) => b.ageMin - a.ageMin );

// Returns null on success, or a short reason the run was not cancelled. A 409
// means the run finished on its own between the list and the cancel.
const cancelRun = async ( runId ) => {
	const response = await ghFetch(
		`${ API_BASE }/repos/${ REPOSITORY }/actions/runs/${ runId }/cancel`,
		{ token: GITHUB_TOKEN, method: 'POST' }
	);
	if ( response.ok ) {
		return null;
	}
	return response.status === 409 ? 'already finished' : `cancel failed: HTTP ${ response.status }`;
};

const summarize = ( lines ) => {
	const text = lines.join( '\n' ) + '\n';
	console.log( text );
	if ( GITHUB_STEP_SUMMARY ) {
		fs.appendFileSync( GITHUB_STEP_SUMMARY, text );
	}
};

const setOutputs = ( outputs ) => {
	if ( ! GITHUB_OUTPUT ) {
		return;
	}
	const text = Object.entries( outputs )
		.map( ( [ key, value ] ) => `${ key }=${ String( value ).replace( /\r?\n/g, ' ' ) }` )
		.join( '\n' ) + '\n';
	fs.appendFileSync( GITHUB_OUTPUT, text );
};

const main = async () => {
	const staleAfterMin = Number( STALE_AFTER_MIN );
	const dryRun = DRY_RUN === '1';
	const overflow = CI_QUEUE_OVERFLOW === undefined || CI_QUEUE_OVERFLOW === '' ? 'unset' : CI_QUEUE_OVERFLOW;
	const runs = await fetchActiveRuns();
	const stale = findStaleRuns( runs, Date.now(), staleAfterMin, GITHUB_RUN_ID );

	// Outputs go first so the Slack step can still report if a cancel fails.
	setOutputs( {
		stale: stale.length > 0,
		count: stale.length,
		details: stale.map( ( run ) => `<${ run.html_url }|${ run.id }> (${ run.status }, ${ Math.round( run.ageMin ) } min)` ).join( ', ' ),
		overflow,
	} );

	const results = [];
	for ( const run of stale ) {
		const reason = dryRun ? 'dry run, not cancelled' : await cancelRun( run.id );
		results.push( { ...run, reason } );
	}

	summarize( [
		'### CI Queue Sentinel watchdog',
		`- Active sentinel runs: ${ runs.length } (this run excluded: ${ GITHUB_RUN_ID || 'n/a' })`,
		`- Stale after: ${ staleAfterMin } min${ dryRun ? ' (dry run)' : '' }`,
		`- CI_QUEUE_OVERFLOW: \`${ overflow }\``,
		...( results.length
			? results.map( ( run ) =>
				`- Stale: [${ run.id }](${ run.html_url }) ${ run.status }, ${ Math.round( run.ageMin ) } min old, ${ run.reason || 'cancelled' }`
			)
			: [ '- No stale runs' ] ),
	] );

	const failed = results.filter( ( run ) => run.reason && run.reason.startsWith( 'cancel failed' ) );
	if ( failed.length ) {
		throw new Error( `${ failed.length } stale run(s) could not be cancelled` );
	}
};

module.exports = { findStaleRuns };

if ( require.main === module ) {
	main().catch( ( error ) => {
		summarize( [ '### CI Queue Sentinel watchdog', `- FAILED: ${ error.message }` ] );
		process.exit( 1 );
	} );
}
