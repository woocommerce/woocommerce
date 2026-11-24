/* eslint-disable @typescript-eslint/no-require-imports -- Exercise the CommonJS module loaded by actions/github-script with Node's test runner. */

const assert = require( 'node:assert/strict' );
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
	const created = [];
	const linearClient = {
		team: async () => ( { id: teamId } ),
		teams: async () => page( [ { id: teamId } ] ),
		issues: async () => page(),
		issueLabel: async () => ( {
			id: labelId,
			team: Promise.resolve( { id: teamId } ),
		} ),
		createIssue: async ( input ) => {
			created.push( input );
			return { success: true, issue: Promise.resolve( issue ) };
		},
		...overrides,
	};
	return {
		created,
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

test( 'creates an issue in the configured team with the PR and verdict', async () => {
	const { created, options } = fixture();
	assert.deepEqual( await ensureDocsIssue( options ), {
		issue,
		reused: false,
	} );
	assert.equal( created.length, 1 );
	assert.equal( created[ 0 ].teamId, teamId );
	assert.deepEqual( created[ 0 ].labelIds, [ labelId ] );
	assert.equal( created[ 0 ].title, `Docs update needed: ${ pr.title }` );
	assert.ok( created[ 0 ].description.includes( pr.html_url ) );
	assert.ok( created[ 0 ].description.includes( verdict.reason ) );
} );

test( 'resolves an exact team key to the team UUID', async () => {
	const { created, options } = fixture( {
		team: async () => {
			throw new Error( 'A team key is not a UUID' );
		},
	} );
	await ensureDocsIssue( { ...options, teamId: ' DOCS ' } );
	assert.equal( created[ 0 ].teamId, teamId );
} );

for ( const missing of [ 'teamId', 'labelId' ] ) {
	test( `rejects a missing ${ missing }`, async () => {
		const { created, options } = fixture();
		await assert.rejects(
			ensureDocsIssue( { ...options, [ missing ]: ' ' } ),
			/Set both/
		);
		assert.equal( created.length, 0 );
	} );
}

for ( const nodes of [ [], [ { id: teamId }, { id: 'other-team' } ] ] ) {
	test( `does not guess a team when key lookup returns ${ nodes.length } matches`, async () => {
		const { created, options } = fixture( {
			teams: async () => page( nodes ),
		} );
		await assert.rejects(
			ensureDocsIssue( { ...options, teamId: 'DOCS' } ),
			/Configured Linear team was not found/
		);
		assert.equal( created.length, 0 );
	} );
}

test( 'reuses an existing issue even if the PR title has changed', async () => {
	const { created, options } = fixture( {
		issues: async () => page( [ issue ] ),
	} );
	assert.deepEqual(
		await ensureDocsIssue( { ...options, pr: { ...pr, title: 'Renamed' } } ),
		{ issue, reused: true }
	);
	assert.equal( created.length, 0 );
} );

test( 'does not confuse a longer PR number with the requested PR', async () => {
	const { created, options } = fixture( {
		issues: async () =>
			page( [ { ...issue, description: `${ issue.description }4` } ] ),
	} );
	assert.equal( ( await ensureDocsIssue( options ) ).reused, false );
	assert.equal( created.length, 1 );
} );

test( 'does not reuse an issue that only mentions the PR', async () => {
	const { created, options } = fixture( {
		issues: async () =>
			page( [
				{ ...issue, description: `Related work: ${ pr.html_url }` },
				{
					...issue,
					description: `**AI reasoning:** See **Pull request:** ${ pr.html_url }`,
				},
			] ),
	} );
	assert.equal( ( await ensureDocsIssue( options ) ).reused, false );
	assert.equal( created.length, 1 );
} );

test( 'reuses an issue whose PR line Linear turned into a link', async () => {
	const { options } = fixture( {
		issues: async () =>
			page( [
				{
					...issue,
					description: `Intro\n\n**Pull request:** [${ pr.html_url }](${ pr.html_url })`,
				},
			] ),
	} );
	assert.equal( ( await ensureDocsIssue( options ) ).reused, true );
} );

test( 'finds an existing issue on a later page', async () => {
	const { created, options } = fixture( {
		issues: async () => ( {
			nodes: [],
			pageInfo: { hasNextPage: true },
			fetchNext: async () => page( [ issue ] ),
		} ),
	} );
	assert.equal( ( await ensureDocsIssue( options ) ).reused, true );
	assert.equal( created.length, 0 );
} );

test( 'does not create an issue when the existing-issue lookup fails', async () => {
	const { created, options } = fixture( {
		issues: async () => ( {
			nodes: [],
			pageInfo: { hasNextPage: true },
			fetchNext: async () => {
				throw new Error( 'Request failed' );
			},
		} ),
	} );
	await assert.rejects( ensureDocsIssue( options ), /No issue was created/ );
	assert.equal( created.length, 0 );
} );

test( 'accepts workspace labels and labels inherited from a parent team', async () => {
	for ( const labelTeam of [ undefined, { id: 'parent-team' } ] ) {
		const { created, options } = fixture( {
			team: async () => ( {
				id: teamId,
				parent: Promise.resolve( { id: 'parent-team' } ),
			} ),
			issueLabel: async () => ( { id: labelId, team: labelTeam } ),
		} );
		await ensureDocsIssue( options );
		assert.equal( created.length, 1 );
	}
} );

test( 'rejects a label belonging to an unrelated team', async () => {
	const { created, options } = fixture( {
		issueLabel: async () => ( { id: labelId, team: { id: 'other-team' } } ),
	} );
	await assert.rejects(
		ensureDocsIssue( options ),
		/label belonging to the configured documentation team/
	);
	assert.equal( created.length, 0 );
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
