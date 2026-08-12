/**
 * CI Queue Sentinel: toggles the CI_QUEUE_OVERFLOW repository variable that
 * ci.yml uses to route e2e jobs to the paid runner group. On when the oldest
 * queued job waits longer than the threshold; off once the queue is healthy
 * again and the state is older than the hysteresis window. MODE=on|off forces.
 */

const fs = require( 'node:fs' );

const {
	REPOSITORY,
	GITHUB_TOKEN,
	SENTINEL_TOKEN,
	MODE = 'auto',
	QUEUE_AGE_THRESHOLD_MIN = '5',
	HYSTERESIS_MIN = '20',
	GITHUB_STEP_SUMMARY,
} = process.env;

const VARIABLE_NAME = 'CI_QUEUE_OVERFLOW';
const API_BASE = 'https://api.github.com';
// Runs outside these created_at windows are ignored: stranded runs from past
// platform incidents would otherwise waste API budget or fake a weeks-old
// queue age. The in_progress window exceeds any real run lifetime, so an
// alive run is never dropped. Exceeding a probe bound marks the probe
// incomplete, which suppresses switch-OFF (see decide()); an over-bound burst
// can also delay a switch-ON by one tick, self-healing as the window rotates.
const PROBE_QUEUED_RUN_MAX_AGE_MIN = 120;
const PROBE_IN_PROGRESS_RUN_MAX_AGE_MIN = 480;
const MAX_QUEUED_RUNS_TO_PROBE = 30;
const MAX_IN_PROGRESS_RUNS_TO_PROBE = 30;
const MAX_RUN_LIST_PAGES = 3;
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

const fetchActiveRuns = async () => {
	// Only queued/in_progress runs can hold jobs competing for runner slots.
	// Other non-final states (waiting/pending/requested/action_required) are
	// gated on approvals, concurrency, or pre-processing — their jobs are not
	// yet dispatchable, so those runs are deliberately ignored.
	const byId = new Map();
	let complete = true;
	const nowMs = Date.now();
	const cutoffFor = ( status ) => nowMs - ( status === 'queued'
		? PROBE_QUEUED_RUN_MAX_AGE_MIN
		: PROBE_IN_PROGRESS_RUN_MAX_AGE_MIN ) * 60 * 1000;
	for ( const status of [ 'queued', 'in_progress' ] ) {
		const cutoffMs = cutoffFor( status );
		for ( let page = 1; ; page++ ) {
			const data = await ghJson(
				`${ API_BASE }/repos/${ REPOSITORY }/actions/runs?status=${ status }&per_page=100&page=${ page }`,
				{ token: GITHUB_TOKEN }
			);
			const runs = data.workflow_runs || [];
			for ( const run of runs ) {
				byId.set( run.id, run );
			}
			if ( runs.length < 100 ) {
				break;
			}
			// Newest-first list: a full page ending past the cutoff means all
			// unseen runs would be age-filtered anyway — still complete.
			if ( new Date( runs[ runs.length - 1 ].created_at ).getTime() < cutoffMs ) {
				break;
			}
			if ( page >= MAX_RUN_LIST_PAGES ) {
				complete = false;
				break;
			}
		}
	}
	const all = [ ...byId.values() ];
	const runs = all
		.filter( ( run ) => new Date( run.created_at ).getTime() >= cutoffFor( run.status ) )
		.sort( ( a, b ) => new Date( a.created_at ) - new Date( b.created_at ) );
	return { runs, complete, dropped: all.length - runs.length };
};

const fetchJobsForRun = async ( runId ) => {
	const jobs = [];
	let page = 1;
	while ( true ) {
		const data = await ghJson(
			`${ API_BASE }/repos/${ REPOSITORY }/actions/runs/${ runId }/jobs?per_page=100&page=${ page }`,
			{ token: GITHUB_TOKEN }
		);
		jobs.push( ...( data.jobs || [] ) );
		if ( jobs.length >= ( data.total_count || 0 ) || ( data.jobs || [] ).length === 0 ) {
			break;
		}
		page++;
	}
	return jobs;
};

const fetchQueuedJobs = async ( runs ) => {
	const allQueued = runs.filter( ( run ) => run.status === 'queued' );
	const allInProgress = runs.filter( ( run ) => run.status !== 'queued' );
	const queuedRuns = allQueued.slice( 0, MAX_QUEUED_RUNS_TO_PROBE );
	const inProgressRuns = allInProgress.slice( 0, MAX_IN_PROGRESS_RUNS_TO_PROBE );
	const queued = [];
	for ( const run of [ ...queuedRuns, ...inProgressRuns ] ) {
		const jobs = await fetchJobsForRun( run.id );
		for ( const job of jobs ) {
			if ( job.status === 'queued' ) {
				queued.push( job );
			}
		}
	}
	const complete = allQueued.length <= MAX_QUEUED_RUNS_TO_PROBE &&
		allInProgress.length <= MAX_IN_PROGRESS_RUNS_TO_PROBE;
	return { queued, complete };
};

const fetchVariable = async () => {
	const response = await ghFetch(
		`${ API_BASE }/repos/${ REPOSITORY }/actions/variables/${ VARIABLE_NAME }`,
		{ token: SENTINEL_TOKEN }
	);
	if ( response.status === 404 ) {
		return null;
	}
	if ( ! response.ok ) {
		throw new Error( `Reading ${ VARIABLE_NAME } failed: HTTP ${ response.status }` );
	}
	return response.json();
};

const writeVariable = async ( value, exists ) => {
	const response = exists
		? await ghFetch(
				`${ API_BASE }/repos/${ REPOSITORY }/actions/variables/${ VARIABLE_NAME }`,
				{ token: SENTINEL_TOKEN, method: 'PATCH', body: { name: VARIABLE_NAME, value } }
		  )
		: await ghFetch( `${ API_BASE }/repos/${ REPOSITORY }/actions/variables`, {
				token: SENTINEL_TOKEN,
				method: 'POST',
				body: { name: VARIABLE_NAME, value },
		  } );
	if ( ! response.ok ) {
		// Fail loudly so an expired token can't silently strand the switch.
		throw new Error( `Writing ${ VARIABLE_NAME }=${ value } failed: HTTP ${ response.status }` );
	}
};

/**
 * @param {Object}      input
 * @param {number|null} input.oldestAgeMin   Minutes the oldest queued job has waited; null if none.
 * @param {string}      input.current       Current value ('1' or '0').
 * @param {number}      input.updatedAtMs   When the variable last changed.
 * @param {number}      input.nowMs
 * @param {string}      input.mode          'auto' | 'on' | 'off'.
 * @param {number}      input.thresholdMin
 * @param {number}      input.hysteresisMin
 * @param {boolean}     input.probeComplete
 * @return {string} '1' or '0'.
 */
const decide = ( { oldestAgeMin, current, updatedAtMs, nowMs, mode, thresholdMin, hysteresisMin, probeComplete } ) => {
	if ( mode === 'on' ) {
		return '1';
	}
	if ( mode === 'off' ) {
		return '0';
	}
	if ( oldestAgeMin !== null && oldestAgeMin > thresholdMin ) {
		return '1';
	}
	// Switch-OFF requires a complete probe: a truncated one may have missed
	// over-threshold jobs, and un-routing mid-congestion is the costly mistake.
	if ( current === '1' && probeComplete && nowMs - updatedAtMs >= hysteresisMin * 60 * 1000 ) {
		return '0';
	}
	return current;
};

const summarize = ( lines ) => {
	const text = lines.join( '\n' ) + '\n';
	console.log( text );
	if ( GITHUB_STEP_SUMMARY ) {
		fs.appendFileSync( GITHUB_STEP_SUMMARY, text );
	}
};

const main = async () => {
	// Forced modes skip the probe: a manual override must succeed even when
	// the queue API is failing, and needs no queue data to decide.
	const forced = MODE === 'on' || MODE === 'off';
	let runs = [], queuedJobs = [], probeComplete = true, dropped = 0;
	if ( ! forced ) {
		const runsResult = await fetchActiveRuns();
		const jobsResult = await fetchQueuedJobs( runsResult.runs );
		runs = runsResult.runs;
		queuedJobs = jobsResult.queued;
		probeComplete = runsResult.complete && jobsResult.complete;
		dropped = runsResult.dropped;
	}
	// Measure ages after the probe; retries can stretch it by minutes.
	const nowMs = Date.now();
	const oldestAgeMin = queuedJobs.length
		? Math.max( ...queuedJobs.map( ( job ) => ( nowMs - new Date( job.created_at ) ) / 60000 ) )
		: null;

	const variable = await fetchVariable();
	// Anything but '1' counts as off; non-canonical values get rewritten.
	const rawValue = variable ? variable.value : null;
	const current = rawValue === '1' ? '1' : '0';
	const updatedAtMs = variable ? new Date( variable.updated_at ).getTime() : 0;

	const value = decide( {
		oldestAgeMin,
		current,
		updatedAtMs,
		nowMs,
		mode: MODE,
		thresholdMin: Number( QUEUE_AGE_THRESHOLD_MIN ),
		hysteresisMin: Number( HYSTERESIS_MIN ),
		probeComplete,
	} );

	if ( value !== rawValue ) {
		await writeVariable( value, !! variable );
	}

	const probed =
		Math.min( runs.filter( ( run ) => run.status === 'queued' ).length, MAX_QUEUED_RUNS_TO_PROBE ) +
		Math.min( runs.filter( ( run ) => run.status !== 'queued' ).length, MAX_IN_PROGRESS_RUNS_TO_PROBE );
	summarize( [
		'### CI Queue Sentinel',
		`- Mode: \`${ MODE }\``,
		...( forced ? [ '- Probe skipped (forced mode)' ] : [
			`- Active runs probed: ${ probed } of ${ runs.length }${ dropped ? ` (${ dropped } dropped by age window)` : '' }${ probeComplete ? '' : ' (probe truncated — switch-off suppressed)' }`,
			`- Queued jobs found: ${ queuedJobs.length }`,
			`- Oldest queued job age: ${ oldestAgeMin === null ? 'n/a (queue clear)' : `${ oldestAgeMin.toFixed( 1 ) } min` } (threshold ${ QUEUE_AGE_THRESHOLD_MIN } min)`,
		] ),
		`- ${ VARIABLE_NAME }: \`${ rawValue }\` -> \`${ value }\`${ value === rawValue ? ' (no change)' : '' }`,
	] );
};

main().catch( ( error ) => {
	summarize( [ '### CI Queue Sentinel', `- FAILED: ${ error.message }` ] );
	process.exit( 1 );
} );
