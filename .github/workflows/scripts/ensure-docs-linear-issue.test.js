/* eslint-disable @typescript-eslint/no-require-imports -- Exercise the CommonJS module loaded by actions/github-script with Node's test runner. */

const assert = require( 'node:assert/strict' );
const { readFileSync } = require( 'node:fs' );
const { join } = require( 'node:path' );
const test = require( 'node:test' );

const ensureDocsIssue = require( './ensure-docs-linear-issue' );

const teamId = '11111111-1111-4111-8111-111111111111';
const labelId = '22222222-2222-4222-8222-222222222222';
const pr = {
	title: 'Update the merchant\'s "Order" screen',
	html_url: 'https://github.com/woocommerce/woocommerce/pull/123',
};
const issue = {
	id: '33333333-3333-4333-8333-333333333333',
	identifier: 'DOCS-1',
	url: 'https://linear.app/example/issue/DOCS-1',
	description: `**Pull request:** ${ pr.html_url }`,
};
const verdict = {
	needs_docs: true,
	confidence: 'high',
	reason: 'Changes an order management screen.',
};

const page = ( nodes = [] ) => ( {
	nodes,
	pageInfo: { hasNextPage: false },
} );

const fixture = ( overrides = {} ) => {
	const calls = [];
	const linearClient = {
		team: async ( id ) => {
			calls.push( [ 'team', id ] );
			return { id: teamId };
		},
		teams: async ( input ) => {
			calls.push( [ 'teams', input ] );
			return page( [ { id: teamId } ] );
		},
		issues: async ( input ) => {
			calls.push( [ 'issues', input ] );
			return page();
		},
		issueLabel: async ( id ) => {
			calls.push( [ 'issueLabel', id ] );
			return { id: labelId, team: Promise.resolve( { id: teamId } ) };
		},
		createIssue: async ( input ) => {
			calls.push( [ 'createIssue', input ] );
			return { success: true, issue: Promise.resolve( issue ) };
		},
		...overrides,
	};
	return {
		calls,
		options: {
			linearClient,
			teamId,
			labelId,
			pr,
			repoFullName: 'woocommerce/woocommerce',
			verdict,
		},
	};
};

test( 'validates the team and label, then creates an issue with event data', async () => {
	const { calls, options } = fixture();
	assert.deepEqual( await ensureDocsIssue( options ), {
		issue,
		reused: false,
	} );
	assert.deepEqual(
		calls.map( ( call ) => call[ 0 ] ),
		[ 'team', 'issues', 'issueLabel', 'createIssue' ]
	);
	const input = calls.at( -1 )[ 1 ];
	assert.equal( input.teamId, teamId );
	assert.deepEqual( input.labelIds, [ labelId ] );
	assert.equal( input.title, `Docs update needed: ${ pr.title }` );
	assert.match(
		input.description,
		/\*\*Repository:\*\* woocommerce\/woocommerce/
	);
	assert.ok( input.description.includes( pr.html_url ) );
	assert.ok( input.description.includes( verdict.reason ) );
} );

test( 'trims configuration without changing the intended team or label', async () => {
	const { calls, options } = fixture();
	await ensureDocsIssue( {
		...options,
		teamId: ` ${ teamId }\n`,
		labelId: ` ${ labelId } `,
	} );
	assert.equal( calls[ 0 ][ 1 ], teamId );
	assert.deepEqual( calls.at( -1 )[ 1 ].labelIds, [ labelId ] );
} );

test( 'resolves an exact team key to the canonical UUID used by issueCreate', async () => {
	const { calls, options } = fixture();
	await ensureDocsIssue( { ...options, teamId: 'DOCS' } );
	assert.deepEqual( calls[ 0 ], [
		'teams',
		{ filter: { key: { eq: 'DOCS' } } },
	] );
	assert.equal( calls.at( -1 )[ 1 ].teamId, teamId );
} );

for ( const missing of [ 'teamId', 'labelId' ] ) {
	test( `rejects missing ${ missing } before contacting Linear`, async () => {
		const { calls, options } = fixture();
		await assert.rejects(
			ensureDocsIssue( { ...options, [ missing ]: ' ' } ),
			/Set both/
		);
		assert.equal( calls.length, 0 );
	} );
}

for ( const nodes of [ [], [ { id: teamId }, { id: 'other-team' } ] ] ) {
	test( `does not guess a team when key lookup returns ${ nodes.length } matches`, async () => {
		const { calls, options } = fixture( {
			teams: async () => page( nodes ),
		} );
		await assert.rejects(
			ensureDocsIssue( { ...options, teamId: 'DOCS' } ),
			/Configured Linear team was not found/
		);
		assert.equal( calls.length, 0 );
	} );
}

test( 'fails an inaccessible team without leaking SDK request details', async () => {
	const { calls, options } = fixture( {
		team: async () => {
			throw new Error( 'secret-request-value Entity not found: Team' );
		},
	} );
	await assert.rejects( ensureDocsIssue( options ), ( error ) => {
		assert.match(
			error.message,
			/Check LINEAR_DOCS_TEAM_ID.*LINEAR_OAUTH_TOKEN/
		);
		assert.ok( ! error.message.includes( 'secret-request-value' ) );
		return true;
	} );
	assert.equal( calls.length, 0 );
} );

test( 'reuses a previously created issue even if the PR title has changed', async () => {
	const { calls, options } = fixture( {
		issues: async () => page( [ issue ] ),
	} );
	assert.deepEqual(
		await ensureDocsIssue( {
			...options,
			pr: { ...pr, title: 'Renamed' },
		} ),
		{ issue, reused: true }
	);
	assert.deepEqual(
		calls.map( ( call ) => call[ 0 ] ),
		[ 'team' ]
	);
} );

test( 'includes archived issues when checking for an existing ticket', async () => {
	const { calls, options } = fixture();
	await ensureDocsIssue( options );
	assert.deepEqual( calls.find( ( call ) => call[ 0 ] === 'issues' )[ 1 ], {
		filter: {
			team: { id: { eq: teamId } },
			description: { contains: pr.html_url },
		},
		includeArchived: true,
	} );
} );

test( 'does not confuse a longer PR number with the requested PR', async () => {
	const { calls, options } = fixture( {
		issues: async () =>
			page( [ { ...issue, description: `${ issue.description }4` } ] ),
	} );
	assert.equal( ( await ensureDocsIssue( options ) ).reused, false );
	assert.equal( calls.at( -1 )[ 0 ], 'createIssue' );
} );

test( 'finds an existing issue on a later page', async () => {
	const { calls, options } = fixture( {
		issues: async () => ( {
			nodes: [ { ...issue, description: `${ issue.description }4` } ],
			pageInfo: { hasNextPage: true },
			fetchNext: async () => page( [ issue ] ),
		} ),
	} );
	assert.equal( ( await ensureDocsIssue( options ) ).reused, true );
	assert.deepEqual(
		calls.map( ( call ) => call[ 0 ] ),
		[ 'team' ]
	);
} );

test( 'aborts rather than creating a duplicate if the existing-issue lookup fails', async () => {
	const { calls, options } = fixture( {
		issues: async () => {
			throw new Error( 'Request failed' );
		},
	} );
	await assert.rejects( ensureDocsIssue( options ), /No issue was created/ );
	assert.deepEqual(
		calls.map( ( call ) => call[ 0 ] ),
		[ 'team' ]
	);
} );

test( 'aborts if pagination fails instead of assuming no issue exists', async () => {
	const { calls, options } = fixture( {
		issues: async () => ( {
			nodes: [],
			pageInfo: { hasNextPage: true },
			fetchNext: async () => {
				throw new Error( 'Request failed' );
			},
		} ),
	} );
	await assert.rejects( ensureDocsIssue( options ), /No issue was created/ );
	assert.deepEqual(
		calls.map( ( call ) => call[ 0 ] ),
		[ 'team' ]
	);
} );

test( 'accepts workspace-wide labels', async () => {
	const { options } = fixture( {
		issueLabel: async () => ( { id: labelId, team: undefined } ),
	} );
	assert.equal( ( await ensureDocsIssue( options ) ).reused, false );
} );

test( 'rejects labels belonging to a different team', async () => {
	const { calls, options } = fixture( {
		issueLabel: async () => ( { id: labelId, team: { id: 'other-team' } } ),
	} );
	await assert.rejects(
		ensureDocsIssue( options ),
		/label belonging to the configured documentation team/
	);
	assert.ok( ! calls.some( ( call ) => call[ 0 ] === 'createIssue' ) );
} );

test( 'rejects an inaccessible label before creating an issue', async () => {
	const { calls, options } = fixture( {
		issueLabel: async () => {
			throw new Error( 'Not found' );
		},
	} );
	await assert.rejects(
		ensureDocsIssue( options ),
		/Check LINEAR_DOCS_AUTO_LABEL/
	);
	assert.ok( ! calls.some( ( call ) => call[ 0 ] === 'createIssue' ) );
} );

for ( const result of [
	{ success: false, issue },
	{ success: true },
	{ success: true, issue: { id: issue.id } },
] ) {
	test( `fails when Linear does not confirm a complete issue: ${ JSON.stringify( result ) }`, async () => {
		const { options } = fixture( { createIssue: async () => result } );
		await assert.rejects(
			ensureDocsIssue( options ),
			/Linear did not confirm issue creation/
		);
	} );
}

test( 'recovers a lost create response on the next run without duplicating the issue', async () => {
	let created = false;
	let mutations = 0;
	const { options } = fixture( {
		issues: async () => page( created ? [ issue ] : [] ),
		createIssue: async () => {
			mutations++;
			created = true;
			throw new Error( 'Response lost after server committed the issue' );
		},
	} );
	await assert.rejects(
		ensureDocsIssue( options ),
		/next run will check for an existing issue first/
	);
	assert.equal( ( await ensureDocsIssue( options ) ).reused, true );
	assert.equal( mutations, 1 );
} );

const workflow = readFileSync(
	join( __dirname, '../docs-needed-detection.yml' ),
	'utf8'
);

test( 'serializes reruns and labels only after issue creation succeeds', () => {
	assert.match( workflow, /cancel-in-progress: false/ );
	assert.ok(
		workflow.indexOf( "name: 'Create Linear Issue'" ) <
			workflow.indexOf( "name: 'Apply Label'" )
	);
	assert.match(
		workflow,
		/needs_docs=true, confidence="high", reason="Already labeled"/
	);
	assert.ok( ! workflow.includes( 'continue-on-error:' ) );
} );

test( 'the GitHub Actions entry point fails the step on a Linear error', async () => {
	const entryPoint = workflow
		.split( "      - name: 'Create Linear Issue'" )[ 1 ]
		.split( '          script: |\n' )[ 1 ]
		.split( '\n\n      # Only mark' )[ 0 ]
		.split( '\n' )
		.map( ( line ) => line.slice( 12 ) )
		.join( '\n' );
	const AsyncFunction = Object.getPrototypeOf( async () => {} ).constructor;
	const run = new AsyncFunction(
		'require',
		'process',
		'context',
		'core',
		entryPoint
	);
	const failures = [];
	const { options } = fixture( {
		team: async () => {
			throw new Error( 'Entity not found: Team' );
		},
	} );
	class LinearClient {
		constructor() {
			Object.assign( this, options.linearClient );
		}
	}
	await run(
		( name ) =>
			name === '@linear/sdk' ? { LinearClient } : ensureDocsIssue,
		{
			env: {
				LINEAR_API_KEY: 'test-only-not-a-credential',
				LINEAR_TEAM_ID: teamId,
				LINEAR_LABEL_ID: labelId,
				STRUCTURED_OUTPUT: JSON.stringify( verdict ),
			},
		},
		{
			payload: {
				pull_request: pr,
				repository: { full_name: options.repoFullName },
			},
		},
		{ setFailed: ( message ) => failures.push( message ) }
	);
	assert.equal( failures.length, 1 );
	assert.match( failures[ 0 ], /Cannot read the configured Linear team/ );
} );
