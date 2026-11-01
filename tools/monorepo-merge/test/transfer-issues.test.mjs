import assert from 'node:assert/strict';
import { describe, it } from 'node:test';

import {
	findFieldsToRestore,
	readProjectFields,
} from '../src/transfer-issues.mjs';

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
} );
