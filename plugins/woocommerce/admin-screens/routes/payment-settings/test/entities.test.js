/**
 * External dependencies
 */
import path from 'path';
import { createRegistry } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ENTITY_KIND, getOfflineEntities } from '../entities';

jest.doMock(
	require.resolve( '@wordpress/block-editor', {
		paths: [ path.dirname( require.resolve( '@wordpress/core-data' ) ) ],
	} ),
	() => ( {} )
);
const { store: coreStore } = jest.requireActual( '@wordpress/core-data' );

it( 'keeps pending virtual-order and shipping edits in the same gateway record', async () => {
	const registry = createRegistry();
	registry.register( coreStore );
	const core = registry.dispatch( coreStore );
	await core.addEntities( getOfflineEntities() );
	await core.receiveEntityRecords( ENTITY_KIND, 'cod', {
		id: 'cod',
		values: {
			instructions: 'Keep these instructions',
			enable_for_virtual: 'yes',
			enable_for_methods: [],
		},
	} );
	core.finishResolution( 'getEntityRecord', [ ENTITY_KIND, 'cod', 'cod' ] );

	await core.editEntityRecord( ENTITY_KIND, 'cod', 'cod', {
		values: { enable_for_virtual: 'no' },
	} );
	await core.editEntityRecord( ENTITY_KIND, 'cod', 'cod', {
		values: { enable_for_methods: [ 'flat_rate' ] },
	} );

	expect(
		registry
			.select( coreStore )
			.getEditedEntityRecord( ENTITY_KIND, 'cod', 'cod' )
	).toEqual( {
		id: 'cod',
		values: {
			instructions: 'Keep these instructions',
			enable_for_virtual: 'no',
			enable_for_methods: [ 'flat_rate' ],
		},
	} );
} );
