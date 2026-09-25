/**
 * The consumer side of local-first CI.
 *
 * A contributor runs eligible CI jobs on their machine with `gh local-ci`,
 * which publishes one check run per attempted job (a "receipt") on the
 * commit through a GitHub App. This script decides, for one matrix job,
 * whether such a receipt lets the job skip its install and test steps.
 *
 * The decision is deliberately one-sided: every path that is not a verified,
 * matching, successful receipt ends in `substituted=false` with a reason.
 * Uncertainty runs the tests. CI has no dependency on the tool; the contract
 * is `.github/local-ci.json`, read from the base branch so a pull request
 * cannot repoint it.
 *
 * `decide()` is pure and is what test.js exercises; `lookup()` gathers its
 * inputs with Octokit and writes the outputs.
 */

const crypto = require( 'crypto' );

const CONFIG_PATH = '.github/local-ci.json';
const TRUSTED_ASSOCIATIONS = [ 'MEMBER', 'OWNER' ];
const RECEIPT_VERSION = 'v1';
const API_TIMEOUT_MS = 20000;

/**
 * Parses a receipt's external_id: `v1;author=…;base=…;config=…;…`.
 * Unknown keys are ignored; a foreign version returns null.
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
 * Deterministic spot-check bucket for a (commit, job) pair: 0..99. The same
 * pair always lands in the same bucket, so a re-run is reproducible and an
 * audit can recompute it.
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
 * Finds the eligibility rule the tool would have used for this job, the way
 * the tool does: the job type is the `[type]` suffix of the name, the rule's
 * optional namePrefix must prefix the name, and no exclude may match.
 *
 * @param {Object} config
 * @param {string} jobName
 * @return {Object|null}
 */
function ruleFor( config, jobName ) {
	const m = /\[([^\]]+)\]\s*(\(optional\))?\s*$/.exec( jobName );
	const type = m ? m[ 1 ] : '';
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
 * @param {string}      input.disabled       The kill-switch variable's value.
 * @param {string}      input.eventName      The workflow event.
 * @param {Object}      input.pullRequest    The pull_request payload.
 * @param {string}      input.jobName        The matrix job name.
 * @param {Object|null} input.baseConfig     `{ raw, parsed, sha256 }` from the base branch, or null when absent.
 * @param {string|null} input.headConfigRaw  The config on the head commit, or null when absent.
 * @param {Array}       input.checkRuns      Check runs on the head commit.
 * @return {{substituted: boolean, reason: string, spotCheck: boolean}}
 */
function decide( input ) {
	const no = ( reason ) => ( { substituted: false, reason, spotCheck: false } );

	if ( String( input.disabled ) === '1' ) {
		return no( 'kill switch LOCAL_CI_RECEIPTS_DISABLED=1' );
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
	if ( input.headConfigRaw !== input.baseConfig.raw ) {
		return no( `${ CONFIG_PATH } differs between the pull request and the base branch; config changes always get full CI` );
	}

	const login = String( ( pr.user && pr.user.login ) || '' );
	const rollout = config.rollout;
	if ( rollout ) {
		const listed = ( rollout.authors || [] ).some( ( a ) => a.toLowerCase() === login.toLowerCase() );
		const labelled = !! rollout.label && ( pr.labels || [] ).some( ( l ) => l.name === rollout.label );
		if ( ! listed && ! labelled ) {
			return no( `author ${ login } is not in the rollout allowlist and the pull request has no "${ rollout.label || '' }" label` );
		}
	}
	if ( ! TRUSTED_ASSOCIATIONS.includes( pr.author_association ) ) {
		return no( `author association ${ pr.author_association } is not one of ${ TRUSTED_ASSOCIATIONS.join( '/' ) }` );
	}

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
	if ( receipt.conclusion !== 'success' ) {
		return no( `newest receipt ${ receipt.html_url || receipt.id } concluded ${ receipt.conclusion }` );
	}

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
	const bucket = sampleBucket( pr.head.sha, input.jobName );
	if ( bucket < Number( rule.sampleRate || 0 ) ) {
		return {
			substituted: false,
			spotCheck: true,
			reason: `spot check: bucket ${ bucket } < sampleRate ${ rule.sampleRate }; running anyway to verify receipt ${ receipt.html_url || receipt.id }`,
		};
	}

	let reason = `receipt ${ receipt.html_url || receipt.id } by ${ meta.author }`;
	if ( meta.base && pr.base && meta.base !== pr.base.sha ) {
		reason += ` (base moved since: tested against ${ meta.base.slice( 0, 11 ) }, base is now ${ String( pr.base.sha ).slice( 0, 11 ) })`;
	}
	return { substituted: true, reason, spotCheck: false };
}

/**
 * Races a promise against the API budget so a slow GitHub cannot stall the
 * job; the caller treats a timeout like any other failure and runs the tests.
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
 * Fetches a file's raw bytes at a ref, or null when it does not exist there.
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
 * Gathers the inputs, decides, and writes the outputs. Never throws: any
 * failure becomes `substituted=false` with the error as the reason.
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
			jobName: process.env.JOB_NAME,
			baseConfig: null,
			headConfigRaw: null,
			checkRuns: [],
		};
		if ( pr ) {
			const repo = { owner: context.repo.owner, repo: context.repo.repo };
			const baseRaw = await rawFile( github, repo, pr.base.sha );
			if ( baseRaw !== null ) {
				input.baseConfig = {
					raw: baseRaw,
					parsed: JSON.parse( baseRaw ),
					sha256: crypto.createHash( 'sha256' ).update( baseRaw ).digest( 'hex' ),
				};
				input.headConfigRaw = await rawFile( github, repo, pr.head.sha );
				input.checkRuns = await within(
					github.paginate( github.rest.checks.listForRef, {
						...repo,
						ref: pr.head.sha,
						app_id: Number( input.baseConfig.parsed.app && input.baseConfig.parsed.app.appId ),
						per_page: 100,
					} ),
					'listing check runs'
				);
			}
		}
		result = decide( input );
	} catch ( e ) {
		result = { substituted: false, spotCheck: false, reason: `lookup failed, running normally: ${ e && e.message ? e.message : e }` };
	}
	core.info( `substituted=${ result.substituted } reason=${ result.reason }` );
	core.setOutput( 'substituted', String( result.substituted ) );
	core.setOutput( 'reason', result.reason );
	core.setOutput( 'spot-check', String( result.spotCheck ) );
	return result;
}

module.exports = { decide, lookup, parseExternalId, sampleBucket, ruleFor, CONFIG_PATH };
