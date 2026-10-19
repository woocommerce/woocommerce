/**
 * External dependencies
 */
import path from 'path';
import { createRegistry } from '@wordpress/data';

/**
 * Internal dependencies
 */
import { ENTITY_KIND, getCoreEntities } from '../entities';

jest.doMock(
	require.resolve( '@wordpress/block-editor', {
		paths: [ path.dirname( require.resolve( '@wordpress/core-data' ) ) ],
	} ),
	() => ( {} )
);
const { store: coreStore } = jest.requireActual( '@wordpress/core-data' );

async function getRegistry() {
	const registry = createRegistry();
	registry.register( coreStore );
	await registry.dispatch( coreStore ).addEntities( getCoreEntities() );
	return registry;
}

it( 'keeps the label, title and fields from the entity config', async () => {
	const registry = await getRegistry();
	const config = registry
		.select( coreStore )
		.getEntityConfig( ENTITY_KIND, 'cheque' );

	expect( config.label ).toBe( 'Check payments' );
	expect( config.getTitle() ).toBe( 'Check payments' );
	expect( config.fields.map( ( field ) => field.id ) ).toEqual( [
		'enabled',
		'title',
		'description',
	] );
} );

it( 'keeps pending edits to several fields in the same record', async () => {
	const registry = await getRegistry();
	const core = registry.dispatch( coreStore );
	await core.receiveEntityRecords( ENTITY_KIND, 'cheque', {
		id: 'cheque',
		enabled: false,
		title: 'Check payments',
		description: 'Pay by check.',
	} );
	core.finishResolution( 'getEntityRecord', [
		ENTITY_KIND,
		'cheque',
		'cheque',
	] );

	await core.editEntityRecord( ENTITY_KIND, 'cheque', 'cheque', {
		enabled: true,
	} );
	await core.editEntityRecord( ENTITY_KIND, 'cheque', 'cheque', {
		title: 'Pay by check',
	} );

	expect(
		registry
			.select( coreStore )
			.getEntityRecordNonTransientEdits( ENTITY_KIND, 'cheque', 'cheque' )
	).toEqual( { enabled: true, title: 'Pay by check' } );
} );
