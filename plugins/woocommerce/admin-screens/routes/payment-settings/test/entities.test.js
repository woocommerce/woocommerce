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
