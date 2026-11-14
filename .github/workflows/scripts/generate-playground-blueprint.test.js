const assert = require( 'node:assert/strict' );
const test = require( 'node:test' );

const {
	run,
	generateWordpressPlaygroundBlueprint,
	generatePlaygroundUrl,
	generateJurassicNinjaUrl,
	generateCommentBody,
} = require( './generate-playground-blueprint' );

const RUN_ID = 123456;
const PR_NUMBER = 789;
const BRANCH = 'fix/some-bug';

const createGithub = ( comments ) => {
	const calls = { created: [], updated: [] };
	return {
		calls,
		rest: {
			issues: {
				listComments: async () => ( { data: comments } ),
				createComment: async ( args ) => calls.created.push( args ),
				updateComment: async ( args ) => calls.updated.push( args ),
			},
		},
	};
};

const context = {
	repo: { owner: 'woocommerce', repo: 'woocommerce' },
	issue: { number: PR_NUMBER },
	runId: RUN_ID,
	payload: { pull_request: { head: { ref: BRANCH } } },
};

test( 'blueprint installs the build of the run and creates a sample product', () => {
	const blueprint = generateWordpressPlaygroundBlueprint( RUN_ID, PR_NUMBER );

	assert.equal(
		blueprint.steps[ 0 ].pluginData.url,
		'https://playground.wordpress.net/plugin-proxy.php?org=woocommerce&repo=woocommerce&workflow=Build%20Live%20Branch&artifact=plugins-123456&pr=789'
	);
	const stepNames = blueprint.steps.map( ( step ) => step.step );
	assert.deepEqual( stepNames, [
		'installPlugin',
		'installPlugin',
		'setSiteOptions',
		'runPHP',
		'login',
	] );
	assert.match( blueprint.steps[ 3 ].code, /new WC_Product_Simple\(\)/ );
} );

test( 'blueprint skips the setup wizard and stores orders in HPOS tables', () => {
	const blueprint = generateWordpressPlaygroundBlueprint( RUN_ID, PR_NUMBER );

	assert.deepEqual( blueprint.steps[ 2 ].options, {
		woocommerce_onboarding_profile: { skipped: true },
		woocommerce_custom_orders_table_enabled: 'yes',
	} );
} );

test( 'playground link cannot end a Markdown link early and decodes to the blueprint', () => {
	const blueprint = generateWordpressPlaygroundBlueprint( RUN_ID, PR_NUMBER );
	const url = generatePlaygroundUrl( blueprint );
	const prefix = 'https://playground.wordpress.net/#';

	assert.ok( url.startsWith( prefix ) );
	assert.doesNotMatch( url, /[\s()']/ );
	assert.deepEqual(
		JSON.parse( decodeURIComponent( url.slice( prefix.length ) ) ),
		blueprint
	);
} );

test( 'jurassic ninja link keeps slashes and encodes other characters', () => {
	assert.equal(
		generateJurassicNinjaUrl( 'fix/some-bug' ),
		'https://jurassic.ninja/create/?jetpack-beta&nojetpack&branches.woocommerce=fix/some-bug'
	);
	assert.equal(
		generateJurassicNinjaUrl( 'fix/a&b#c(d)' ),
		'https://jurassic.ninja/create/?jetpack-beta&nojetpack&branches.woocommerce=fix/a%26b%23c%28d%29'
	);
} );

test( 'comment lists the three options for the pull request', () => {
	const body = generateCommentBody( {
		prNumber: PR_NUMBER,
		branch: BRANCH,
		playgroundUrl: 'https://playground.wordpress.net/#blueprint',
	} );

	assert.ok( body.includes( '## Test the build' ) );
	assert.ok(
		body.includes(
			'searching for PR number `789` or branch name `fix/some-bug` in your-test.site/wp-admin/admin.php?page=jetpack-beta&plugin=woocommerce'
		)
	);
	assert.ok(
		body.includes(
			'[Launch a JN site with this branch](https://jurassic.ninja/create/?jetpack-beta&nojetpack&branches.woocommerce=fix/some-bug)'
		)
	);
	assert.ok(
		body.includes(
			'[Test this pull request with WordPress Playground](https://playground.wordpress.net/#blueprint)'
		)
	);
} );

test( 'creates the comment when the pull request has none', async () => {
	const github = createGithub( [
		{ id: 1, user: { type: 'User' }, body: '## Test the build' },
		{ id: 2, user: { type: 'Bot' }, body: 'Unrelated bot comment' },
	] );

	await run( { github, context } );

	assert.equal( github.calls.updated.length, 0 );
	assert.equal( github.calls.created.length, 1 );
	assert.equal( github.calls.created[ 0 ].issue_number, 789 );
	assert.ok( github.calls.created[ 0 ].body.includes( '## Test the build' ) );
} );

test( 'updates the comment it posted before', async () => {
	const github = createGithub( [
		{ id: 5, user: { type: 'Bot' }, body: '## Test the build\n...' },
	] );

	await run( { github, context } );

	assert.equal( github.calls.created.length, 0 );
	assert.equal( github.calls.updated.length, 1 );
	assert.equal( github.calls.updated[ 0 ].comment_id, 5 );
} );

test( 'updates a comment posted under the previous heading', async () => {
	const github = createGithub( [
		{
			id: 7,
			user: { type: 'Bot' },
			body: '## Test using WordPress Playground\n...',
		},
	] );

	await run( { github, context } );

	assert.equal( github.calls.created.length, 0 );
	assert.equal( github.calls.updated.length, 1 );
	assert.equal( github.calls.updated[ 0 ].comment_id, 7 );
	assert.ok( github.calls.updated[ 0 ].body.includes( '## Test the build' ) );
} );
