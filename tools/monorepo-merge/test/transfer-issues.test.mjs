import assert from 'node:assert/strict';
import { describe, it } from 'node:test';

import { closePrompts } from '../src/confirm.mjs';
import {
	findFieldsToRestore,
	readProjectFields,
	transferIssues,
} from '../src/transfer-issues.mjs';

describe( 'transferIssues dry run', () => {
	// A prompt writes "(y/N)" and, with stdin held open, waits forever, so the spy and the timeout each catch one.
	it(
		'lists the issues without prompting or sending mutations',
		{
			timeout: 5000,
		},
		async ( t ) => {
			const previousToken = process.env.GH_TOKEN;
			process.env.GH_TOKEN = 'token';
			t.after( () => {
				closePrompts();
				if ( previousToken === undefined ) {
					delete process.env.GH_TOKEN;
				} else {
					process.env.GH_TOKEN = previousToken;
				}
			} );
			const writes = t.mock.method( process.stdout, 'write' );
			const queries = [];
			t.mock.method( globalThis, 'fetch', async ( url, { body } ) => {
				const { query } = JSON.parse( body );
				queries.push( query );

				let data = { viewer: { id: 'viewer' } };
				if ( query.includes( 'issueCount' ) ) {
					data = { search: { issueCount: 1 } };
				} else if ( query.includes( 'labels(' ) ) {
					data = {
						repository: {
							labels: {
								nodes: [ { id: 'label-id', name: 'migrated' } ],
								pageInfo: { hasNextPage: false },
							},
						},
					};
				} else if ( query.includes( 'search(' ) ) {
					data = {
						search: {
							nodes: [
								{
									id: 'issue-id',
									title: 'An issue',
									projectItems: { nodes: [] },
								},
							],
							pageInfo: { hasNextPage: false },
						},
					};
				} else if ( query.includes( 'repository(' ) ) {
					data = { repository: { id: 'repo-id' } };
				}

				return Response.json( { data } );
			} );
			const log = t.mock.method( console, 'log', () => {} );

			await transferIssues( 'woocommerce/example', {
				destination: 'woocommerce/woocommerce',
				searchFilter: 'is:open',
				labels: 'migrated',
				dryRun: true,
			} );

			assert.equal(
				queries.filter( ( query ) => /^\s*mutation/.test( query ) )
					.length,
				0
			);
			assert.ok(
				! writes.mock.calls.some( ( call ) =>
					String( call.arguments[ 0 ] ).includes( '(y/N)' )
				),
				'A dry run must not prompt'
			);
			const output = log.mock.calls.map(
				( call ) => call.arguments[ 0 ]
			);
			for ( const line of [
				'- "An issue" (0 project field value(s) to check)',
				'No changes were made.',
			] ) {
				assert.ok( output.includes( line ), `Missing "${ line }"` );
			}
		}
	);
} );

// Mirrors the live API: value types without a fragment come back as `{}`, and Title has its own data type.
function projectItem( itemId, projectId, optionId ) {
	const fieldValues = [
		{},
		{
			field: {
				id: `${ projectId }-title`,
				name: 'Title',
				dataType: 'TITLE',
			},
			text: 'Issue title',
		},
	];
	if ( optionId ) {
		fieldValues.push( {
			field: {
				id: `${ projectId }-status`,
				name: 'Status',
				dataType: 'SINGLE_SELECT',
			},
			optionId,
		} );
	}

	return {
		id: itemId,
		project: { id: projectId, title: `Project ${ projectId }` },
		fieldValues: { nodes: fieldValues },
	};
}

describe( 'findFieldsToRestore', () => {
	const before = readProjectFields( [
		projectItem( 'old-a', 'A', 'todo' ),
		projectItem( 'old-b', 'B', 'done' ),
	] );

	it( 'restores nothing when every project kept its values', () => {
		assert.deepEqual(
			findFieldsToRestore( before, [
				projectItem( 'new-a', 'A', 'todo' ),
				projectItem( 'new-b', 'B', 'done' ),
			] ),
			{ missingProjects: [], fieldsToRestore: [] }
		);
	} );

	it( 'restores lost values on the new project item', () => {
		const { missingProjects, fieldsToRestore } = findFieldsToRestore(
			before,
			[ projectItem( 'new-a', 'A', 'todo' ), projectItem( 'new-b', 'B' ) ]
		);

		assert.deepEqual( missingProjects, [] );
		assert.deepEqual(
			fieldsToRestore.map( ( { itemId, value } ) => ( {
				itemId,
				value,
			} ) ),
			[ { itemId: 'new-b', value: { singleSelectOptionId: 'done' } } ]
		);
	} );

	it( 'restores values that changed during the transfer', () => {
		const { fieldsToRestore } = findFieldsToRestore( before, [
			projectItem( 'new-a', 'A', 'in-progress' ),
			projectItem( 'new-b', 'B', 'done' ),
		] );

		assert.deepEqual(
			fieldsToRestore.map( ( { itemId, value } ) => ( {
				itemId,
				value,
			} ) ),
			[ { itemId: 'new-a', value: { singleSelectOptionId: 'todo' } } ]
		);
	} );

	it( 'reports projects the issue is no longer in', () => {
		assert.deepEqual(
			findFieldsToRestore( before, [
				projectItem( 'new-a', 'A', 'todo' ),
			] ),
			{ missingProjects: [ 'Project B' ], fieldsToRestore: [] }
		);
	} );

	it( 'reports a lost project that had no values to restore', () => {
		assert.deepEqual(
			findFieldsToRestore( [], [], [ { id: 'C', title: 'Project C' } ] ),
			{ missingProjects: [ 'Project C' ], fieldsToRestore: [] }
		);
	} );
} );
