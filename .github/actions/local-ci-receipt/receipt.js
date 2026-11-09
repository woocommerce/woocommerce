/**
 * Decides whether a `gh local-ci` receipt (a check run on the head commit)
 * lets this matrix job skip its install and test steps. Anything short of
 * a verified, passing receipt yields `substituted=false` with a reason.
 * A sampled job runs anyway, and so does one whose receipt failed; for
 * both, `report()` compares CI's result with the receipt's after the tests.
 * `decide()` and `parityReport()` are pure; `lookup()` and `report()` do
 * the I/O. See README.md.
 */

const crypto = require( 'crypto' );

const CONFIG_PATH = '.github/local-ci.json';
const WRITE_PERMISSIONS = [ 'admin', 'maintain', 'write' ];
const OFF_VALUES = [ '', '0', 'false', 'off', 'no' ];
// Only job types whose remaining steps ci.yml guards.
const SUBSTITUTABLE_TYPES = [ 'unit' ];
const RECEIPT_VERSION = 'v1';
// Local results worth comparing with CI's; `cancelled` is not a result.
const COMPARABLE_CONCLUSIONS = [ 'success', 'failure', 'timed_out' ];
const API_TIMEOUT_MS = 20000;

/**
 * Parses `v1;author=…;base=…;config=…`; null for any other version.
 *
 * @param {string} externalId
 * @return {Object|null}
 */
function parseExternalId( externalId ) {
	const parts = String( externalId || '' ).split( ';' );
	if ( parts[ 0 ] !== RECEIPT_VERSION ) {
		return null;
	}
	const meta = {};
	for ( const kv of parts.slice( 1 ) ) {
		const i = kv.indexOf( '=' );
		if ( i > 0 ) {
			meta[ kv.slice( 0, i ) ] = kv.slice( i + 1 );
		}
	}
	return meta;
}

/**
 * Anything but an explicit "off" value disables, so a typo fails safe.
 *
 * @param {*} value
 * @return {boolean}
 */
function killSwitchSet( value ) {
	return ! OFF_VALUES.includes( String( value === undefined || value === null ? '' : value ).trim().toLowerCase() );
}

/**
 * Deterministic sample bucket for a (commit, job) pair: 0..99. The same
 * pair always lands in the same bucket, so a re-run samples the same jobs
 * and anyone can recompute the draw.
 *
 * @param {string} headSha
 * @param {string} jobName
 * @return {number}
 */
function sampleBucket( headSha, jobName ) {
	const digest = crypto.createHash( 'sha256' ).update( headSha + jobName ).digest();
	return digest.readUInt32BE( 0 ) % 100;
}

/**
 * @param {string} jobName
 * @return {string} The `[type]` suffix, or "".
 */
function jobType( jobName ) {
	const m = /\[([^\]]+)\]\s*(\(optional\))?\s*$/.exec( jobName );
	return m ? m[ 1 ] : '';
}

/**
 * The rule that claims this job, matched the way gh local-ci matches it.
 *
 * @param {Object} config
 * @param {string} jobName
 * @return {Object|null}
 */
function ruleFor( config, jobName ) {
	const type = jobType( jobName );
	for ( const rule of config.eligible || [] ) {
		if ( rule.type !== type ) {
			continue;
		}
		if ( rule.namePrefix && ! jobName.startsWith( rule.namePrefix ) ) {
			continue;
		}
		if ( ( rule.exclude || [] ).some( ( x ) => jobName.includes( x ) ) ) {
			continue;
		}
		return rule;
	}
	return null;
}

/**
 * Decides whether the job may be substituted. Pure.
 *
 * @param {Object}      input
 * @param {string}      input.disabled         Kill-switch variable value.
 * @param {string}      input.eventName
 * @param {Object}      input.pullRequest      pull_request payload.
 * @param {string|null} input.authorPermission Repository permission, or null.
 * @param {string}      input.jobName
 * @param {Object|null} input.baseConfig       `{ raw, parsed, sha256 }`, or null.
 * @param {string|null} input.headConfigRaw
 * @param {Array}       input.checkRuns
 * @return {{substituted: boolean, reason: string, parity?: Object}} `parity`
 *         is set when CI runs the job and its result can be compared with
 *         the receipt's.
 */
function decide( input ) {
	const no = ( reason ) => ( { substituted: false, reason } );

	if ( killSwitchSet( input.disabled ) ) {
		return no( `kill switch LOCAL_CI_RECEIPTS_DISABLED=${ input.disabled }` );
	}
	if ( input.eventName !== 'pull_request' || ! input.pullRequest ) {
		return no( `event is ${ input.eventName }, receipts apply to pull_request only` );
	}
	const pr = input.pullRequest;

	if ( ! input.baseConfig || ! input.baseConfig.parsed ) {
		return no( `no ${ CONFIG_PATH } on the base branch` );
	}
	const config = input.baseConfig.parsed;
	if ( config.enabled !== true ) {
		return no( 'local CI receipts are not enabled on the base branch' );
	}
	// Only the configured base branch's config is trusted.
	const baseRef = pr.base && pr.base.ref;
	if ( baseRef !== config.baseBranch ) {
		return no( `pull request targets ${ baseRef }, receipts apply to ${ config.baseBranch } only` );
	}
	if ( input.headConfigRaw !== input.baseConfig.raw ) {
		return no( `${ CONFIG_PATH } differs between the pull request and the base branch; config changes always get full CI` );
	}

	const login = String( ( pr.user && pr.user.login ) || '' );
	// Write permission is what publishing a receipt requires; nothing weaker
	// (author_association is viewer-dependent) is accepted in its place.
	if ( ! WRITE_PERMISSIONS.includes( String( input.authorPermission || '' ).toLowerCase() ) ) {
		return no( `author ${ login } has ${ input.authorPermission || 'unknown' } repository permission; write permission is required` );
	}
	const trust = `has ${ input.authorPermission } permission`;

	const name = String( config.receiptPrefix || '' ) + input.jobName;
	const appId = Number( config.app && config.app.appId );
	const candidates = ( input.checkRuns || [] ).filter(
		( r ) => r && r.name === name && r.app && Number( r.app.id ) === appId
	);
	if ( candidates.length === 0 ) {
		return no( `no receipt named "${ name }" from app ${ appId }` );
	}
	// Newest wins: a later failure supersedes an earlier success.
	const receipt = candidates.reduce( ( a, b ) => ( Number( b.id ) > Number( a.id ) ? b : a ) );
	const receiptRef = receipt.html_url || receipt.id;

	const meta = parseExternalId( receipt.external_id );
	if ( ! meta ) {
		return no( `receipt ${ receipt.id } is not a ${ RECEIPT_VERSION } receipt` );
	}
	if ( String( meta.author || '' ).toLowerCase() !== login.toLowerCase() ) {
		return no( `receipt was published by ${ meta.author || 'nobody' }, pull request is by ${ login }` );
	}
	if ( meta.config !== input.baseConfig.sha256 ) {
		return no( 'receipt was made under a different local-ci.json than the base branch has' );
	}

	const rule = ruleFor( config, input.jobName );
	if ( ! rule ) {
		return no( 'no eligibility rule for this job in the base config' );
	}
	if ( ! SUBSTITUTABLE_TYPES.includes( jobType( input.jobName ) ) ) {
		return no( `ci.yml does not guard the steps a ${ jobType( input.jobName ) } job needs; only ${ SUBSTITUTABLE_TYPES.join( '/' ) } jobs may be substituted` );
	}
	// The conclusion is checked after the receipt is known to be valid, so a
	// failed one is still worth comparing: CI runs that job anyway.
	const parity = ( kind, extra = {} ) => ( {
		kind,
		receipt: { id: receipt.id, url: receipt.html_url || '', conclusion: receipt.conclusion },
		author: meta.author,
		mode: meta.mode || '',
		tool: meta.tool || '',
		base: meta.base || '',
		ciBase: ( pr.base && pr.base.sha ) || '',
		...extra,
	} );
	if ( receipt.conclusion !== 'success' ) {
		const result = no( `newest receipt ${ receiptRef } concluded ${ receipt.conclusion }` );
		if ( COMPARABLE_CONCLUSIONS.includes( receipt.conclusion ) ) {
			result.parity = parity( 'failed-receipt' );
		}
		return result;
	}

	const bucket = sampleBucket( String( ( pr.head && pr.head.sha ) || '' ), input.jobName );
	// A rate that is not a number fails closed: sample everything rather
	// than nothing.
	const rate = rule.sampleRate === undefined ? 0 : Number( rule.sampleRate );
	if ( ! Number.isFinite( rate ) || bucket < rate ) {
		return {
			substituted: false,
			reason: `parity sample: bucket ${ bucket } < sampleRate ${ rule.sampleRate }; running in CI to compare with receipt ${ receiptRef }`,
			parity: parity( 'sample', { bucket, rate: rule.sampleRate } ),
		};
	}

	let reason = `receipt ${ receiptRef } by ${ meta.author } (${ trust })`;
	if ( meta.base && pr.base && meta.base !== pr.base.sha ) {
		reason += ` (base moved since: tested against ${ meta.base.slice( 0, 11 ) }, base is now ${ String( pr.base.sha ).slice( 0, 11 ) })`;
	}
	return { substituted: true, reason };
}

/**
 * @param {string} sha
 * @return {string} The 11-character short form, or "unknown".
 */
function short( sha ) {
	return sha ? String( sha ).slice( 0, 11 ) : 'unknown';
}

/**
 * Compares CI's result with the receipt's. Pure.
 *
 * @param {Object} input
 * @param {Object} input.parity    `decide()`'s `parity`.
 * @param {string} input.ciOutcome The `Run tests` step's outcome.
 * @param {string} input.jobName
 * @return {{result: string, level: string, marker: string, message: string, markdown: string}}
 */
function parityReport( { parity, ciOutcome, jobName } ) {
	const local = parity.receipt.conclusion;
	const ci = ciOutcome || 'unknown';
	const baseMoved = Boolean( parity.base && parity.ciBase && parity.base !== parity.ciBase );
	const localPassed = local === 'success';

	let result;
	let level;
	let message;
	if ( ci !== 'success' && ci !== 'failure' ) {
		result = 'inconclusive';
		level = 'notice';
		message = `CI's tests did not finish (${ ci }), so there is nothing to compare with the receipt.`;
	} else if ( localPassed === ( ci === 'success' ) ) {
		result = 'agree';
		level = 'notice';
		message = `CI ${ localPassed ? 'passed' : 'failed' }, as the receipt did.`;
	} else {
		result = 'mismatch';
		// A local pass that CI fails is the case that would let a bad change
		// through on a receipt; the other way round costs only a re-run.
		level = localPassed ? 'error' : 'warning';
		message = `the receipt ${ localPassed ? 'passed' : 'failed' } and CI ${ ci === 'success' ? 'passed' : 'failed' }.`;
		if ( baseMoved ) {
			message += ' The base moved since the receipt, so the difference may come from the base branch.';
		}
	}

	const marker = `local-ci-parity result=${ result } local=${ local } ci=${ ci } base-moved=${ baseMoved ? 'yes' : 'no' } kind=${ parity.kind }`;
	const receiptLink = parity.receipt.url ? `[receipt ${ parity.receipt.id }](${ parity.receipt.url })` : `receipt ${ parity.receipt.id }`;
	const why =
		parity.kind === 'sample'
			? `sampled (bucket ${ parity.bucket } < sampleRate ${ parity.rate })`
			: 'the newest receipt did not pass';
	const base = baseMoved
		? `the receipt tested against ${ short( parity.base ) }; CI merged onto ${ short( parity.ciBase ) }`
		: `the same base, ${ short( parity.ciBase ) }`;
	const markdown = [
		`### Local CI parity: ${ result }`,
		'',
		`${ jobName }: ${ message }`,
		'',
		`- **Receipt:** ${ receiptLink }, ${ local }, by ${ parity.author } (mode ${ parity.mode || 'unknown' }, tool ${ parity.tool || 'unknown' })`,
		`- **CI:** ${ ci }`,
		`- **Why CI ran it:** ${ why }`,
		`- **Base:** ${ base }`,
		'',
		result === 'mismatch' ? 'See "When CI and a receipt disagree" in `.github/actions/local-ci-receipt/README.md`.' : '',
		'',
		`\`${ marker }\``,
		'',
	].join( '\n' );

	return { result, level, marker, message: `${ marker }: ${ message } ${ parity.receipt.url || parity.receipt.id }`, markdown };
}

/**
 * Writes the comparison as an annotation and a job summary section. Never
 * throws and never fails the job: CI's own result is already the job's.
 *
 * @param {Object} args
 * @param {Object} args.core
 * @return {Promise<Object|null>}
 */
async function report( { core } ) {
	try {
		const out = parityReport( {
			parity: JSON.parse( process.env.PARITY ),
			ciOutcome: process.env.CI_OUTCOME,
			jobName: process.env.JOB_NAME,
		} );
		core[ out.level ]( out.message, { title: `Local CI parity: ${ out.result }` } );
		await core.summary.addRaw( out.markdown ).write();
		return out;
	} catch ( e ) {
		core.warning( `local-ci: parity report failed: ${ e && e.message ? e.message : e }` );
		return null;
	}
}

/**
 * Rejects after API_TIMEOUT_MS so a slow API cannot stall the job.
 *
 * @param {Promise} promise
 * @param {string}  what
 * @return {Promise}
 */
function within( promise, what ) {
	let timer;
	const timeout = new Promise( ( _, reject ) => {
		timer = setTimeout( () => reject( new Error( `${ what } took longer than ${ API_TIMEOUT_MS }ms` ) ), API_TIMEOUT_MS );
	} );
	return Promise.race( [ promise, timeout ] ).finally( () => clearTimeout( timer ) );
}

/**
 * The config file's raw bytes at a ref, or null when absent.
 *
 * @param {Object} github
 * @param {Object} repo   `{ owner, repo }`
 * @param {string} ref
 * @return {Promise<string|null>}
 */
async function rawFile( github, repo, ref ) {
	try {
		const res = await within(
			github.rest.repos.getContent( { ...repo, path: CONFIG_PATH, ref, mediaType: { format: 'raw' } } ),
			`reading ${ CONFIG_PATH } at ${ ref }`
		);
		return typeof res.data === 'string' ? res.data : null;
	} catch ( e ) {
		if ( e && e.status === 404 ) {
			return null;
		}
		throw e;
	}
}

/**
 * The author's repository permission, or null when unavailable.
 *
 * @param {Object} github
 * @param {Object} repo   `{ owner, repo }`
 * @param {string} username
 * @return {Promise<string|null>}
 */
async function authorPermission( github, repo, username ) {
	if ( ! username ) {
		return null;
	}
	try {
		const res = await within(
			github.rest.repos.getCollaboratorPermissionLevel( { ...repo, username } ),
			`reading ${ username }'s permission`
		);
		return ( res.data && res.data.permission ) || null;
	} catch ( e ) {
		return null;
	}
}

/**
 * Gathers the inputs, decides, writes the outputs. Never throws.
 *
 * @param {Object} args
 * @param {Object} args.github
 * @param {Object} args.context
 * @param {Object} args.core
 */
async function lookup( { github, context, core } ) {
	let result;
	try {
		const pr = context.payload.pull_request;
		const input = {
			disabled: process.env.DISABLED,
			eventName: context.eventName,
			pullRequest: pr,
			authorPermission: null,
			jobName: process.env.JOB_NAME,
			baseConfig: null,
			headConfigRaw: null,
			checkRuns: [],
		};
		// No API calls when the switch is set, off a PR, or not enabled.
		const wanted = ! killSwitchSet( input.disabled ) && input.eventName === 'pull_request' && pr;
		if ( wanted ) {
			const repo = { owner: context.repo.owner, repo: context.repo.repo };
			const baseRaw = await rawFile( github, repo, pr.base.sha );
			if ( baseRaw !== null ) {
				input.baseConfig = {
					raw: baseRaw,
					parsed: JSON.parse( baseRaw ),
					sha256: crypto.createHash( 'sha256' ).update( baseRaw ).digest( 'hex' ),
				};
			}
		}
		if ( wanted && input.baseConfig && input.baseConfig.parsed.enabled === true ) {
			const repo = { owner: context.repo.owner, repo: context.repo.repo };
			input.headConfigRaw = await rawFile( github, repo, pr.head.sha );
			input.authorPermission = await authorPermission( github, repo, pr.user && pr.user.login );
			core.info( `author permission lookup: ${ input.authorPermission || 'unavailable to this token' }` );
			// `all`: every attempt, so the newest is chosen here.
			const listed = await within(
				github.rest.checks.listForRef( {
					...repo,
					ref: pr.head.sha,
					check_name: String( input.baseConfig.parsed.receiptPrefix || '' ) + input.jobName,
					app_id: Number( input.baseConfig.parsed.app && input.baseConfig.parsed.app.appId ),
					filter: 'all',
					per_page: 100,
				} ),
				'listing check runs'
			);
			input.checkRuns = ( listed.data && listed.data.check_runs ) || [];
		}
		result = decide( input );
	} catch ( e ) {
		result = { substituted: false, reason: `lookup failed, running normally: ${ e && e.message ? e.message : e }` };
	}
	if ( /lookup failed/.test( result.reason ) ) {
		core.warning( `local-ci: ${ result.reason }` );
	}
	core.info( `substituted=${ result.substituted } reason=${ result.reason }` );
	core.setOutput( 'substituted', String( result.substituted ) );
	core.setOutput( 'reason', result.reason );
	core.setOutput( 'parity', result.parity ? JSON.stringify( result.parity ) : '' );
	return result;
}

module.exports = { decide, lookup, parityReport, report, parseExternalId, sampleBucket, ruleFor, jobType, killSwitchSet, CONFIG_PATH, SUBSTITUTABLE_TYPES };
