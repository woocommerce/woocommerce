import assert from 'node:assert/strict';
import { describe, it } from 'node:test';

import {
	findFieldsToRestore,
	readProjectFields,
} from '../src/transfer-issues.mjs';

function projectItem( itemId, projectId, optionId ) {
	return {
		id: itemId,
		project: { id: projectId, title: `Project ${ projectId }` },
		fieldValues: {
			nodes: optionId
				? [
						{
							field: {
								id: `${ projectId }-status`,
								name: 'Status',
								dataType: 'SINGLE_SELECT',
							},
							optionId,
						},
					]
				: [],
		},
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

	it( 'reports projects the issue is no longer in', () => {
		assert.deepEqual(
			findFieldsToRestore( before, [
				projectItem( 'new-a', 'A', 'todo' ),
			] ),
			{ missingProjects: [ 'Project B' ], fieldsToRestore: [] }
		);
	} );
} );
