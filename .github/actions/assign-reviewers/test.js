/**
 * Checks for assign.js. Plain node, no dependencies:
 *   node .github/actions/assign-reviewers/test.js
 *
 * The glob cases below were all confirmed against minimatch 3.1.2 with
 * { dot: true }, the call the replaced shufo/auto-assign-reviewer-by-files
 * action made. Run this on Linux: path.matchesGlob() is case-insensitive on
 * macOS and Windows, so a case-sensitive pattern passes there when it should
 * not.
 */

const assert = require( 'assert' );
const fs = require( 'fs' );
const os = require( 'os' );
const path = require( 'path' );

const { assignReviewers, undot, matchesAny } = require( './assign.js' );

const workspace = fs.mkdtempSync( path.join( os.tmpdir(), 'assign-reviewers-' ) );
const configFile = ( contents ) => {
	const file = path.join( workspace, `config-${ Object.keys( contents ).join( '' ).length }.json` );
	fs.writeFileSync( file, JSON.stringify( contents ) );

	return file;
};

const matches = ( pattern, file ) => matchesAny( pattern, [ undot( file ) ] );

const ran = [];
const check = ( name, run ) => ran.push( { name, run } );

/* Glob matching. */

check( 'a wildcard matches a dotfile leaf, as minimatch did with { dot: true }', () => {
	assert.ok( matches( 'packages/js/**/*', 'packages/js/components/.npmrc' ) );
	assert.ok( matches( 'docs/**/*', 'docs/_docu-tools/static/.nojekyll' ) );
} );

check( 'a dot opening a brace alternative still matches', () => {
	// Regression: renaming dots after `{` and `,` broke this pattern.
	const pattern = 'plugins/woocommerce/{.wordpress-org,i18n}/**/*';
	assert.ok( matches( pattern, 'plugins/woocommerce/.wordpress-org/icon-128x128.png' ) );
	assert.ok( matches( pattern, 'plugins/woocommerce/i18n/states.php' ) );
} );

check( 'a brace group of extensions is not treated as a segment boundary', () => {
	// Regression: the fix for the case above must not break this one.
	assert.ok( matches( 'src/**/*{.js,.ts}', 'src/a.js' ) );
	assert.ok( matches( 'src/**/*{.js,.ts}', 'src/.hidden.js' ) );
	assert.ok( ! matches( 'src/**/*{.js,.ts}', 'src/a.css' ) );
} );

check( 'a single star does not cross a directory boundary', () => {
	assert.ok( matches( 'plugins/woocommerce/templates/*', 'plugins/woocommerce/templates/a.php' ) );
	assert.ok( ! matches( 'plugins/woocommerce/templates/*', 'plugins/woocommerce/templates/cart/a.php' ) );
} );

check( 'a double star matches at any depth, including none', () => {
	assert.ok( matches( 'a/**/*', 'a/b' ) );
	assert.ok( matches( 'a/**/*', 'a/b/c/d' ) );
	assert.ok( ! matches( 'a/**/*', 'a' ) );
} );

check( 'nested brace groups expand', () => {
	assert.ok( matches( 'a/{b,{c,d}}/*', 'a/d/e.php' ) );
	assert.ok( ! matches( 'a/{b,{c,d}}/*', 'a/e/f.php' ) );
} );

check( 'the shipped configs still parse and name reviewers', () => {
	const community = path.join( __dirname, '../../project-community-pr-assigner.json' );
	const teams = path.join( __dirname, '../../automate-team-review-assignment-config.json' );

	for ( const file of [ community, teams ] ) {
		const config = JSON.parse( fs.readFileSync( file, 'utf8' ) );
		assert.ok( Object.keys( config ).length, `${ file } is empty` );

		for ( const [ key, value ] of Object.entries( config ) ) {
			for ( const owner of [].concat( value ) ) {
				assert.ok( typeof owner === 'string' && owner, `${ key } does not name a reviewer` );
			}
		}
	}
} );

/* Requesting the reviews. */

const run = async ( { config, mode = 'changed-files', changed = [], requestReviewers } ) => {
	const calls = [], warnings = [];
	let failed = null;

	process.env.CONFIG_FILE = config;
	process.env.MODE = mode;

	const github = {
		paginate: async () => changed.map( ( filename ) => ( { filename } ) ),
		rest: {
			pulls: {
				listFiles() {},
				requestReviewers: async ( body ) => {
					calls.push( { reviewers: body.reviewers, team_reviewers: body.team_reviewers } );

					if ( requestReviewers ) {
						await requestReviewers( body );
					}
				},
			},
		},
	};

	await assignReviewers( {
		github,
		context: {
			repo: { owner: 'woocommerce', repo: 'woocommerce' },
			payload: { pull_request: { number: 1, user: { login: 'alice' } } },
		},
		core: { info() {}, warning: ( m ) => warnings.push( m ), setFailed: ( m ) => { failed = m; } },
	} );

	return { calls, warnings, failed };
};

const reject = ( status, message ) => {
	const error = new Error( message );
	error.status = status;

	throw error;
};

const routing = configFile( { 'a/**/*': [ 'rubik', '@Alice' ], 'b/**/*': 'ballade' } );

check( 'a key may name several reviewers, and the author is never one of them', async () => {
	// `@Alice` is the author `alice`; GitHub logins are case-insensitive.
	const { calls, failed } = await run( { config: routing, changed: [ 'a/x.js' ] } );
	assert.deepStrictEqual( calls, [ { reviewers: [], team_reviewers: [ 'rubik' ] } ] );
	assert.strictEqual( failed, null );
} );

check( 'nothing matched asks for nothing and does not fail', async () => {
	const { calls, failed } = await run( { config: routing, changed: [ 'unrouted/z.txt' ] } );
	assert.strictEqual( calls.length, 0 );
	assert.strictEqual( failed, null );
} );

check( 'a rejected batch is retried one reviewer at a time', async () => {
	const { calls, failed } = await run( {
		config: routing,
		changed: [ 'a/x.js', 'b/y.js' ],
		requestReviewers: ( { team_reviewers: requested } ) =>
			requested.length > 1 && reject( 422, 'batch rejected' ),
	} );

	assert.deepStrictEqual( calls.slice( 1 ), [
		{ reviewers: undefined, team_reviewers: [ 'rubik' ] },
		{ reviewers: undefined, team_reviewers: [ 'ballade' ] },
	] );
	assert.strictEqual( failed, null, 'both retries landed, so stay green' );
} );

check( 'a reviewer the API will not accept fails the job and is named', async () => {
	const { failed } = await run( {
		config: routing,
		changed: [ 'b/y.js' ],
		requestReviewers: () => reject( 422, 'no such team' ),
	} );

	assert.match( failed, /ballade/ );
	assert.match( failed, /check the config/ );
} );

check( 'a 403 is the token or the rate limit, not the config', async () => {
	const { failed } = await run( {
		config: routing,
		changed: [ 'b/y.js' ],
		requestReviewers: () => reject( 403, 'rate limited' ),
	} );

	assert.doesNotMatch( failed, /check the config/ );
	assert.match( failed, /anybody/ );
} );

check( 'a malformed config value names the offending key', async () => {
	for ( const value of [ null, '', [], { team: 'rubik' } ] ) {
		await assert.rejects(
			() => run( { config: configFile( { 'a/**/*': value } ), changed: [ 'a/x.js' ] } ),
			/Config key 'a\/\*\*\/\*' does not name a reviewer/,
			`${ JSON.stringify( value ) } should be rejected`
		);
	}
} );

check( 'an unknown mode is refused, including a key from Object.prototype', async () => {
	for ( const mode of [ 'typo', 'toString', '__proto__', '' ] ) {
		await assert.rejects(
			() => run( { config: routing, mode, changed: [ 'a/x.js' ] } ),
			/^Error: Unknown mode/,
			`mode ${ JSON.stringify( mode ) } should be refused`
		);
	}
} );

/* Runner. */

( async () => {
	let failures = 0;

	for ( const { name, run: body } of ran ) {
		try {
			await body();
			console.log( `ok    ${ name }` );
		} catch ( error ) {
			failures += 1;
			console.log( `FAIL  ${ name }\n      ${ error.message.split( '\n' )[ 0 ] }` );
		}
	}

	fs.rmSync( workspace, { recursive: true, force: true } );
	console.log( failures ? `\n${ failures } of ${ ran.length } failed` : `\nall ${ ran.length } checks passed` );
	process.exit( failures ? 1 : 0 );
} )();
