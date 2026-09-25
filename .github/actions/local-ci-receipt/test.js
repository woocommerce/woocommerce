/**
 * Checks for receipt.js. Plain node, no dependencies:
 *   node .github/actions/local-ci-receipt/test.js
 *
 * One case per branch of decide(); every negative asserts its reason, since
 * the reason is what a person reads in the job log and what metrics count.
 */

const assert = require( 'assert' );
const crypto = require( 'crypto' );

const { decide, lookup, parseExternalId, sampleBucket, CONFIG_PATH } = require( './receipt.js' );

const ran = [];
const check = ( name, run ) => ran.push( { name, run } );

/* Fixtures. */

const HEAD = 'a'.repeat( 40 );
const BASE = 'b'.repeat( 40 );
const JOB = 'JavaScript - @acme/number [unit]';

const configObject = ( overrides = {} ) => ( {
	version: 1,
	repository: 'acme/w',
	app: { clientId: 'cid', appId: 4830646 },
	baseBranch: 'trunk',
	receiptPrefix: 'local-ci/v1: ',
	enabled: true,
	rollout: { authors: [ 'octo' ], label: 'local-ci' },
	plan: { command: [ 'true' ] },
	eligible: [ { type: 'unit', namePrefix: 'JavaScript', sampleRate: 0, run: [ 'x' ] } ],
	...overrides,
} );

const baseConfig = ( overrides ) => {
	const raw = JSON.stringify( configObject( overrides ), null, 2 );
	return { raw, parsed: JSON.parse( raw ), sha256: crypto.createHash( 'sha256' ).update( raw ).digest( 'hex' ) };
};

const externalId = ( cfg, extra = {} ) =>
	Object.entries( { author: 'octo', base: BASE, config: cfg.sha256, mode: 'native', reset: 'none', tool: '0.1', ...extra } )
		.map( ( [ k, v ] ) => `${ k }=${ v }` )
		.join( ';' );

const receipt = ( cfg, overrides = {} ) => ( {
	id: 100,
	name: 'local-ci/v1: ' + JOB,
	conclusion: 'success',
	html_url: 'https://github.com/acme/w/runs/100',
	external_id: 'v1;' + externalId( cfg ),
	app: { id: 4830646 },
	...overrides,
} );

const pullRequest = ( overrides = {} ) => ( {
	user: { login: 'octo' },
	author_association: 'MEMBER',
	labels: [],
	head: { sha: HEAD },
	base: { sha: BASE },
	...overrides,
} );

const input = ( overrides = {} ) => {
	const cfg = baseConfig();
	return {
		disabled: '',
		eventName: 'pull_request',
		pullRequest: pullRequest(),
		jobName: JOB,
		baseConfig: cfg,
		headConfigRaw: cfg.raw,
		checkRuns: [ receipt( cfg ) ],
		...overrides,
	};
};

const refuses = ( result, fragment ) => {
	assert.strictEqual( result.substituted, false, `expected refusal, got ${ JSON.stringify( result ) }` );
	assert.ok( result.reason.includes( fragment ), `reason "${ result.reason }" should mention "${ fragment }"` );
};

/* The positive path, and everything that departs from it. */

check( 'a verified successful receipt by the author substitutes', () => {
	const r = decide( input() );
	assert.strictEqual( r.substituted, true, r.reason );
	assert.strictEqual( r.spotCheck, false );
	assert.ok( r.reason.includes( 'runs/100' ) && r.reason.includes( 'by octo' ) );
} );

check( 'the kill switch wins over everything', () => {
	refuses( decide( input( { disabled: '1' } ) ), 'kill switch' );
} );

check( 'only pull_request events substitute (merge_group and push run in full)', () => {
	refuses( decide( input( { eventName: 'merge_group' } ) ), 'pull_request only' );
	refuses( decide( input( { eventName: 'push', pullRequest: undefined } ) ), 'pull_request only' );
} );

check( 'no config on the base branch means no substitution', () => {
	refuses( decide( input( { baseConfig: null } ) ), `no ${ CONFIG_PATH } on the base branch` );
} );

check( 'enabled:false on the base branch means no substitution', () => {
	const cfg = baseConfig( { enabled: false } );
	refuses( decide( input( { baseConfig: cfg, headConfigRaw: cfg.raw, checkRuns: [ receipt( cfg ) ] } ) ), 'not enabled' );
} );

check( 'a pull request that changes the config gets full CI', () => {
	refuses( decide( input( { headConfigRaw: input().baseConfig.raw + '\n' } ) ), 'config changes always get full CI' );
	refuses( decide( input( { headConfigRaw: null } ) ), 'config changes always get full CI' );
} );

check( 'rollout gate: unlisted author without the label is refused, label admits', () => {
	refuses( decide( input( { pullRequest: pullRequest( { user: { login: 'someone' } } ) } ) ), 'not in the rollout allowlist' );
	// The receipt author still has to match the PR author, so give someone their own receipt.
	const cfg = baseConfig();
	const labelled = input( {
		pullRequest: pullRequest( { user: { login: 'someone' }, labels: [ { name: 'local-ci' } ] } ),
		checkRuns: [ receipt( cfg, { external_id: 'v1;' + externalId( cfg, { author: 'someone' } ) } ) ],
	} );
	assert.strictEqual( decide( labelled ).substituted, true );
} );

check( 'no rollout block means every member may substitute', () => {
	const cfg = baseConfig( { rollout: undefined } );
	const r = decide( input( {
		baseConfig: cfg, headConfigRaw: cfg.raw,
		pullRequest: pullRequest( { user: { login: 'anyone' } } ),
		checkRuns: [ receipt( cfg, { external_id: 'v1;' + externalId( cfg, { author: 'anyone' } ) } ) ],
	} ) );
	assert.strictEqual( r.substituted, true, r.reason );
} );

check( 'COLLABORATOR and CONTRIBUTOR are not trusted, OWNER is', () => {
	refuses( decide( input( { pullRequest: pullRequest( { author_association: 'COLLABORATOR' } ) } ) ), 'not one of MEMBER/OWNER' );
	refuses( decide( input( { pullRequest: pullRequest( { author_association: 'CONTRIBUTOR' } ) } ) ), 'not one of MEMBER/OWNER' );
	assert.strictEqual( decide( input( { pullRequest: pullRequest( { author_association: 'OWNER' } ) } ) ).substituted, true );
} );

check( 'no receipt, wrong app, or wrong name is refused', () => {
	const cfg = baseConfig();
	refuses( decide( input( { checkRuns: [] } ) ), 'no receipt named' );
	refuses( decide( input( { checkRuns: [ receipt( cfg, { app: { id: 999 } } ) ] } ) ), 'no receipt named' );
	refuses( decide( input( { checkRuns: [ receipt( cfg, { name: 'local-ci/v1: JavaScript - @acme/other [unit]' } ) ] } ) ), 'no receipt named' );
} );

check( 'the newest receipt wins: a later failure supersedes an earlier success', () => {
	const cfg = baseConfig();
	const older = receipt( cfg, { id: 100, conclusion: 'success' } );
	const newer = receipt( cfg, { id: 101, conclusion: 'failure', html_url: 'https://github.com/acme/w/runs/101' } );
	refuses( decide( input( { checkRuns: [ newer, older ] } ) ), 'runs/101 concluded failure' );
	refuses( decide( input( { checkRuns: [ older, newer ] } ) ), 'runs/101 concluded failure' );
	// And the other way round: a later success after a failure substitutes.
	const fixed = receipt( cfg, { id: 102, conclusion: 'success' } );
	assert.strictEqual( decide( input( { checkRuns: [ newer, fixed, older ] } ) ).substituted, true );
} );

check( 'a receipt with a foreign or missing external_id version is refused', () => {
	const cfg = baseConfig();
	refuses( decide( input( { checkRuns: [ receipt( cfg, { external_id: 'v0;author=octo' } ) ] } ) ), 'not a v1 receipt' );
	refuses( decide( input( { checkRuns: [ receipt( cfg, { external_id: '' } ) ] } ) ), 'not a v1 receipt' );
} );

check( 'a receipt published by someone else is refused', () => {
	const cfg = baseConfig();
	refuses( decide( input( { checkRuns: [ receipt( cfg, { external_id: 'v1;' + externalId( cfg, { author: 'colleague' } ) } ) ] } ) ), 'published by colleague, pull request is by octo' );
} );

check( 'author comparison ignores case', () => {
	const cfg = baseConfig();
	const r = decide( input( { checkRuns: [ receipt( cfg, { external_id: 'v1;' + externalId( cfg, { author: 'Octo' } ) } ) ] } ) );
	assert.strictEqual( r.substituted, true, r.reason );
} );

check( 'a receipt made under a different config hash is refused', () => {
	const cfg = baseConfig();
	refuses( decide( input( { checkRuns: [ receipt( cfg, { external_id: 'v1;' + externalId( cfg, { config: 'f'.repeat( 64 ) } ) } ) ] } ) ), 'different local-ci.json' );
} );

check( 'a job with no eligibility rule in the base config is refused even with a receipt', () => {
	const cfg = baseConfig();
	const name = 'PHP 8.1 - @acme/w [unit:php]';
	refuses( decide( input( { jobName: name, checkRuns: [ receipt( cfg, { name: 'local-ci/v1: ' + name } ) ] } ) ), 'no eligibility rule' );
} );

check( 'deterministic spot check: the bucket decides, and the same pair always lands in the same bucket', () => {
	const bucket = sampleBucket( HEAD, JOB );
	assert.strictEqual( sampleBucket( HEAD, JOB ), bucket );
	assert.ok( bucket >= 0 && bucket < 100 );
	const inSample = baseConfig( { eligible: [ { type: 'unit', namePrefix: 'JavaScript', sampleRate: bucket + 1, run: [ 'x' ] } ] } );
	const hit = decide( input( { baseConfig: inSample, headConfigRaw: inSample.raw, checkRuns: [ receipt( inSample ) ] } ) );
	assert.strictEqual( hit.substituted, false );
	assert.strictEqual( hit.spotCheck, true );
	assert.ok( hit.reason.startsWith( 'spot check' ), hit.reason );
	const outOfSample = baseConfig( { eligible: [ { type: 'unit', namePrefix: 'JavaScript', sampleRate: bucket, run: [ 'x' ] } ] } );
	assert.strictEqual( decide( input( { baseConfig: outOfSample, headConfigRaw: outOfSample.raw, checkRuns: [ receipt( outOfSample ) ] } ) ).substituted, true );
	const everything = baseConfig( { eligible: [ { type: 'unit', namePrefix: 'JavaScript', sampleRate: 100, run: [ 'x' ] } ] } );
	assert.strictEqual( decide( input( { baseConfig: everything, headConfigRaw: everything.raw, checkRuns: [ receipt( everything ) ] } ) ).spotCheck, true );
} );

check( 'base drift is reported but still substitutes (phase-1 policy)', () => {
	const cfg = baseConfig();
	const r = decide( input( { checkRuns: [ receipt( cfg, { external_id: 'v1;' + externalId( cfg, { base: 'c'.repeat( 40 ) } ) } ) ] } ) );
	assert.strictEqual( r.substituted, true );
	assert.ok( r.reason.includes( 'base moved since' ), r.reason );
} );

check( 'parseExternalId ignores unknown keys and refuses other versions', () => {
	assert.deepStrictEqual( parseExternalId( 'v1;author=x;future=y' ), { author: 'x', future: 'y' } );
	assert.strictEqual( parseExternalId( 'v2;author=x' ), null );
	assert.strictEqual( parseExternalId( undefined ), null );
} );

/* lookup(): gathering with a stubbed Octokit. */

const fakeGithub = ( { baseRaw, headRaw, pages } ) => ( {
	rest: {
		repos: {
			getContent: async ( { ref } ) => {
				const raw = ref === HEAD ? headRaw : baseRaw;
				if ( raw === null ) {
					const e = new Error( 'Not Found' );
					e.status = 404;
					throw e;
				}
				return { data: raw };
			},
		},
		checks: { listForRef: 'listForRef' },
	},
	paginate: async ( fn, params ) => {
		assert.strictEqual( fn, 'listForRef' );
		assert.strictEqual( params.app_id, 4830646 );
		assert.strictEqual( params.per_page, 100 );
		return pages.flat();
	},
} );

const fakeCore = () => {
	const outputs = {};
	return { outputs, info: () => {}, setOutput: ( k, v ) => ( outputs[ k ] = v ) };
};

check( 'lookup reads base and head config, pages check runs, and writes outputs', async () => {
	const cfg = baseConfig();
	process.env.JOB_NAME = JOB;
	process.env.DISABLED = '';
	const core = fakeCore();
	const pages = [ new Array( 100 ).fill( { name: 'CI job', app: { id: 4830646 } } ), [ receipt( cfg ) ] ];
	await lookup( {
		github: fakeGithub( { baseRaw: cfg.raw, headRaw: cfg.raw, pages } ),
		context: { eventName: 'pull_request', payload: { pull_request: pullRequest() }, repo: { owner: 'acme', repo: 'w' } },
		core,
	} );
	assert.strictEqual( core.outputs.substituted, 'true', core.outputs.reason );
	assert.strictEqual( core.outputs[ 'spot-check' ], 'false' );
} );

check( 'lookup with no base config refuses without calling for check runs', async () => {
	process.env.JOB_NAME = JOB;
	const core = fakeCore();
	const github = fakeGithub( { baseRaw: null, headRaw: null, pages: [] } );
	github.paginate = async () => assert.fail( 'must not list check runs without a base config' );
	await lookup( { github, context: { eventName: 'pull_request', payload: { pull_request: pullRequest() }, repo: { owner: 'acme', repo: 'w' } }, core } );
	assert.strictEqual( core.outputs.substituted, 'false' );
	assert.ok( core.outputs.reason.includes( 'no .github/local-ci.json on the base branch' ), core.outputs.reason );
} );

check( 'lookup never throws: an API failure runs the job normally', async () => {
	process.env.JOB_NAME = JOB;
	const core = fakeCore();
	const github = fakeGithub( { baseRaw: baseConfig().raw, headRaw: baseConfig().raw, pages: [] } );
	github.paginate = async () => { throw new Error( 'boom 502' ); };
	await lookup( { github, context: { eventName: 'pull_request', payload: { pull_request: pullRequest() }, repo: { owner: 'acme', repo: 'w' } }, core } );
	assert.strictEqual( core.outputs.substituted, 'false' );
	assert.ok( core.outputs.reason.includes( 'lookup failed' ) && core.outputs.reason.includes( 'boom 502' ), core.outputs.reason );
} );

check( 'lookup on a push event refuses without touching the API', async () => {
	const core = fakeCore();
	const github = { rest: { repos: { getContent: async () => assert.fail( 'no API on push' ) } } };
	await lookup( { github, context: { eventName: 'push', payload: {}, repo: { owner: 'acme', repo: 'w' } }, core } );
	assert.strictEqual( core.outputs.substituted, 'false' );
} );

/* Runner. */

( async () => {
	let failed = 0;
	for ( const { name, run } of ran ) {
		try {
			await run();
			console.log( `ok - ${ name }` );
		} catch ( e ) {
			failed += 1;
			console.log( `not ok - ${ name }\n  ${ e.stack || e }` );
		}
	}
	console.log( `\n${ ran.length - failed } passed, ${ failed } failed` );
	process.exit( failed ? 1 : 0 );
} )();
