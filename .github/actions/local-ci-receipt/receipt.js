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
const WRITE_PERMISSIONS = [ 'admin', 'maintain', 'write' ];
const OFF_VALUES = [ '', '0', 'false', 'off', 'no' ];
// Job types whose skipped steps ci.yml actually guards. A config may declare
// more rules than this; substitution for any other type is refused until the
// workflow guards that type's environment and artifact steps too.
const SUBSTITUTABLE_TYPES = [ 'unit' ];
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
 * Whether the kill switch is set. Any value other than an explicit "off"
 * disables substitution, so an operator typing `true` or `yes` during an
 * incident gets what they meant rather than a silent no-op.
 *
 * @param {*} value
 * @return {boolean}
 */
function killSwitchSet( value ) {
	return ! OFF_VALUES.includes( String( value === undefined || value === null ? '' : value ).trim().toLowerCase() );
}

/**
 * The `[type]` suffix of a job name, or "" when there is none.
 *
 * @param {string} jobName
 * @return {string}
 */
function jobType( jobName ) {
	const m = /\[([^\]]+)\]\s*(\(optional\))?\s*$/.exec( jobName );
	return m ? m[ 1 ] : '';
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
 * @param {string}      input.disabled       The kill-switch variable's value.
 * @param {string}      input.eventName      The workflow event.
 * @param {Object}      input.pullRequest    The pull_request payload.
 * @param {string|null} input.authorPermission The author's repository permission (`admin`/`maintain`/`write`/…), or null when unknown.
 * @param {string}      input.jobName        The matrix job name.
 * @param {Object|null} input.baseConfig     `{ raw, parsed, sha256 }` from the base branch, or null when absent.
 * @param {string|null} input.headConfigRaw  The config on the head commit, or null when absent.
 * @param {Array}       input.checkRuns      Check runs on the head commit.
 * @return {{substituted: boolean, reason: string}}
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
	// The trusted config is whatever the PR targets; only the configured
	// base branch's copy counts, so a PR opened against some other branch
	// (a release branch, or one carrying its own config) runs in full.
	const baseRef = pr.base && pr.base.ref;
	if ( baseRef !== config.baseBranch ) {
		return no( `pull request targets ${ baseRef }, receipts apply to ${ config.baseBranch } only` );
	}
	if ( input.headConfigRaw !== input.baseConfig.raw ) {
		return no( `${ CONFIG_PATH } differs between the pull request and the base branch; config changes always get full CI` );
	}

	const login = String( ( pr.user && pr.user.login ) || '' );
	// Who is running the tool is decided on the publisher's side (the
	// allowlist compiled into gh local-ci). What CI checks here is that the
	// author could have published at all: write permission, which is what a
	// receipt requires, or org membership. author_association is the weak
	// one — it is computed for the viewer, and the Actions token cannot see
	// a private org membership, so most members show as CONTRIBUTOR.
	let trust;
	if ( WRITE_PERMISSIONS.includes( String( input.authorPermission || '' ).toLowerCase() ) ) {
		trust = `has ${ input.authorPermission } permission`;
	} else if ( TRUSTED_ASSOCIATIONS.includes( pr.author_association ) ) {
		trust = `is ${ pr.author_association }`;
	} else {
		return no( `author ${ login } has ${ input.authorPermission || 'unknown' } repository permission and association ${ pr.author_association }; write permission or MEMBER/OWNER is required` );
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
	if ( ! SUBSTITUTABLE_TYPES.includes( jobType( input.jobName ) ) ) {
		return no( `ci.yml does not guard the steps a ${ jobType( input.jobName ) } job needs; only ${ SUBSTITUTABLE_TYPES.join( '/' ) } jobs may be substituted` );
	}
	let reason = `receipt ${ receipt.html_url || receipt.id } by ${ meta.author } (${ trust })`;
	if ( meta.base && pr.base && meta.base !== pr.base.sha ) {
		reason += ` (base moved since: tested against ${ meta.base.slice( 0, 11 ) }, base is now ${ String( pr.base.sha ).slice( 0, 11 ) })`;
	}
	return { substituted: true, reason };
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
 * The author's permission on the repository, or null when the token cannot
 * ask (the decision then falls back to author_association).
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
		// A token that may not ask (403) is the same as not knowing.
		return null;
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
			authorPermission: null,
			jobName: process.env.JOB_NAME,
			baseConfig: null,
			headConfigRaw: null,
			checkRuns: [],
		};
		// Fetch only as much as the decision needs: with the kill switch set,
		// on a non-PR event, or while the feature is off on the base branch,
		// every test job would otherwise spend three API calls to learn
		// nothing. decide() refuses on those grounds before it reads what
		// was not fetched.
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
			core.info( `author permission lookup: ${ input.authorPermission || 'unavailable to this token' }; association ${ pr.author_association }` );
			// One call, for the one name this job cares about: the API filters
			// by name and app, and `all` keeps every attempt so the newest can
			// be chosen here rather than trusting the API's idea of latest.
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
	return result;
}

module.exports = { decide, lookup, parseExternalId, ruleFor, jobType, killSwitchSet, CONFIG_PATH, SUBSTITUTABLE_TYPES };
