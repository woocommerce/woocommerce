/**
 * CI Queue Sentinel freshness check: reports when no sentinel run has
 * succeeded inside the freshness window. A cancelled run is not a failure, so
 * `notify-failure` cannot catch a sentinel that stops producing decisions;
 * without this check that silence looks exactly like a healthy quiet queue.
 * Runs outside the sentinel's concurrency group so a wedged run cannot
 * silence it.
 */

const fs = require( 'node:fs' );

const {
	REPOSITORY,
	GITHUB_TOKEN,
	GITHUB_RUN_ID,
	FRESH_AFTER_MIN = '60',
	CADENCE_MIN = '15',
	CI_QUEUE_OVERFLOW,
	GITHUB_STEP_SUMMARY,
	GITHUB_OUTPUT,
} = process.env;

const WORKFLOW_FILE = 'ci-queue-sentinel.yml';
const API_BASE = 'https://api.github.com';
const MAX_FETCH_RETRIES = 2;
// The window only needs the newest success; a page covers a long outage.
const RUNS_PER_PAGE = 30;

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

const fetchRecentSuccesses = async () => {
	const data = await ghJson(
		`${ API_BASE }/repos/${ REPOSITORY }/actions/workflows/${ WORKFLOW_FILE }/runs?status=success&per_page=${ RUNS_PER_PAGE }`,
		{ token: GITHUB_TOKEN }
	);
	return data.workflow_runs || [];
};

/**
 * Decides whether the sentinel has gone quiet, and whether this tick says so.
 *
 * Age is measured from when a run finished, so a long run counts as fresh from
 * its own completion. The current run is excluded: it cannot have succeeded
 * yet, and counting it would make every tick look fresh.
 *
 * Staleness persists until someone fixes it, so alerting on every tick would
 * post four times an hour for as long as the outage lasts. `alert` is true
 * only for a tick landing in the first two cadences of a window, so an outage
 * is reported on the first stale tick and at most twice an hour after that.
 * Inferring the previous tick from `ageMin - cadenceMin` would be tidier, but
 * scheduled runs drift and a delayed tick would then wait a full window for
 * its first alert. The window must stay several cadences wide for this to
 * hold.
 *
 * @param {Object[]}      runs          Runs from the workflow runs API.
 * @param {number}        nowMs
 * @param {number}        freshAfterMin Minutes of silence that count as stale.
 * @param {number}        cadenceMin    Minutes between scheduled ticks.
 * @param {string|number} currentRunId
 * @return {{stale: boolean, alert: boolean, ageMin: number|null, lastSuccess: Object|null}} Freshness verdict.
 */
const evaluateFreshness = ( runs, nowMs, freshAfterMin, cadenceMin, currentRunId ) => {
	const successes = runs
		.filter( ( run ) => String( run.id ) !== String( currentRunId ) )
		.filter( ( run ) => run.conclusion === 'success' )
		.map( ( run ) => ( {
			id: run.id,
			html_url: run.html_url,
			finishedMs: new Date( run.updated_at || run.run_started_at || run.created_at ).getTime(),
		} ) )
		.sort( ( a, b ) => b.finishedMs - a.finishedMs );

	const lastSuccess = successes[ 0 ] || null;
	if ( ! lastSuccess ) {
		// The API filters to successes, so an empty list means the workflow has
		// no retained successful run at all. Rare enough to alert on every tick.
		return { stale: true, alert: true, ageMin: null, lastSuccess: null };
	}

	const ageMin = ( nowMs - lastSuccess.finishedMs ) / 60000;
	const stale = ageMin > freshAfterMin;
	const intoWindowMin = ageMin % freshAfterMin;
	return { stale, alert: stale && intoWindowMin < cadenceMin * 2, ageMin, lastSuccess };
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
	const freshAfterMin = Number( FRESH_AFTER_MIN );
	const cadenceMin = Number( CADENCE_MIN );
	const overflow = CI_QUEUE_OVERFLOW === undefined || CI_QUEUE_OVERFLOW === '' ? 'unset' : CI_QUEUE_OVERFLOW;
	const runs = await fetchRecentSuccesses();
	const { stale, alert, ageMin, lastSuccess } = evaluateFreshness(
		runs,
		Date.now(),
		freshAfterMin,
		cadenceMin,
		GITHUB_RUN_ID
	);

	const describeAge = ageMin === null ? 'never' : `${ Math.round( ageMin ) } min ago`;
	const describeRun = lastSuccess ? `<${ lastSuccess.html_url }|${ lastSuccess.id }>` : 'none in recent history';

	setOutputs( {
		alert,
		age: describeAge,
		last: describeRun,
		overflow,
	} );

	summarize( [
		'### CI Queue Sentinel freshness',
		`- Last successful run: ${ lastSuccess ? `[${ lastSuccess.id }](${ lastSuccess.html_url })` : 'none in recent history' }`,
		`- Last success: ${ describeAge } (window ${ freshAfterMin } min)`,
		`- ${ stale ? 'STALE' : 'Fresh' }${ stale && ! alert ? ', already reported this window' : '' }`,
		`- CI_QUEUE_OVERFLOW: \`${ overflow }\``,
	] );
};

module.exports = { evaluateFreshness };

// Finding the sentinel stale does not fail this step. A failure here marks the
// run unsuccessful, which the next tick would read as a sentinel outage. Only
// an unusable API fails the step.
if ( require.main === module ) {
	main().catch( ( error ) => {
		summarize( [ '### CI Queue Sentinel freshness', `- FAILED: ${ error.message }` ] );
		process.exit( 1 );
	} );
}
