// Preloaded with `node --import` to stand in for GitHub: three transferable issues whose project
// values are lost, and a rate limit on the first attempt to restore one. The 25-second wait is skipped.
const realSetTimeout = globalThis.setTimeout;
globalThis.setTimeout = ( callback, ms, ...args ) =>
	realSetTimeout( callback, Math.min( ms, 10 ), ...args );

const field = { id: 'F', name: 'Status', dataType: 'SINGLE_SELECT' };

globalThis.fetch = async ( url, { body } ) => {
	const { query, variables } = JSON.parse( body );

	if ( query.includes( 'updateProjectV2ItemFieldValue' ) ) {
		process.stderr.write( 'MOCK field update\n' );
		return Response.json( {
			errors: [
				{ type: 'RATE_LIMITED', message: 'API rate limit exceeded' },
			],
		} );
	}
	if ( query.includes( 'transferIssue' ) ) {
		return Response.json( {
			data: {
				transferIssue: {
					issue: { id: `${ variables.input.issueId }-new` },
				},
			},
		} );
	}
	if ( query.includes( 'node(' ) ) {
		return Response.json( {
			data: {
				node: {
					projectItems: {
						nodes: [
							{
								id: `${ variables.id }-item`,
								project: { id: 'P', title: 'Board' },
								fieldValues: { nodes: [] },
							},
						],
					},
				},
			},
		} );
	}
	if ( query.includes( 'issueCount' ) ) {
		return Response.json( { data: { search: { issueCount: 3 } } } );
	}
	if ( query.includes( 'search(' ) ) {
		return Response.json( {
			data: {
				search: {
					nodes: [ 1, 2, 3 ].map( ( number ) => ( {
						id: `issue-${ number }`,
						title: `Issue ${ number }`,
						projectItems: {
							nodes: [
								{
									id: `old-${ number }`,
									project: { id: 'P', title: 'Board' },
									fieldValues: {
										nodes: [ { field, optionId: 'todo' } ],
									},
								},
							],
						},
					} ) ),
					pageInfo: { hasNextPage: false },
				},
			},
		} );
	}
	if ( query.includes( 'repository(' ) ) {
		return Response.json( { data: { repository: { id: 'R' } } } );
	}

	return Response.json( { data: { viewer: { id: 'viewer' } } } );
};
