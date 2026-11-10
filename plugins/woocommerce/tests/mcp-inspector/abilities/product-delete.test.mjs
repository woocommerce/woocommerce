import assert from 'node:assert/strict';
import { after, before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/product-delete';

const SKU = 'mcp-delete';

const lookUp = async ( id ) =>
	( await execute( 'woocommerce/products-query', { id } ) ).products;

// The tests run in order: the product goes to the trash first, then away for good.
describe( ABILITY, () => {
	let id;
	before( async () => {
		await seed( 'remove-products', { skus: [ SKU ] } );
		( { id } = await seed( 'product', { sku: SKU } ) );
	} );
	after( () => seed( 'remove-products', { skus: [ SKU ] } ) );

	test( 'moves the product to the trash by default', async () => {
		assert.deepEqual( await execute( ABILITY, { id } ), {
			id,
			status: 'trash',
		} );
		assert.deepEqual(
			( await lookUp( id ) ).map( ( { status } ) => status ),
			[ 'trash' ]
		);
	} );

	test( 'deletes it for good with force', async () => {
		assert.deepEqual( await execute( ABILITY, { id, force: true } ), {
			id,
			status: 'deleted',
		} );
		await refused(
			'woocommerce/products-query',
			{ id },
			/Product not found/
		);
	} );

	test( 'refuses a product that is gone', () =>
		refused( ABILITY, { id }, /permission/i ) );
} );

// Product saves write shared rows, such as a term count, so two at once can deadlock. The loser of the deadlock must
// fail instead of reporting a write that the database rolled back.
describe( `${ ABILITY } while another product is saved`, () => {
	const SKUS = [ 'mcp-delete-race', 'mcp-delete-race-other' ];
	let trashed;
	let other;
	before( async () => {
		await seed( 'remove-products', { skus: SKUS } );
		trashed = await seed( 'product', { sku: SKUS[ 0 ] } );
		other = await seed( 'product', { sku: SKUS[ 1 ] } );
	} );
	after( () => seed( 'remove-products', { skus: SKUS } ) );

	test( 'reports the trash only when it happened', async () => {
		const [ result ] = await Promise.allSettled( [
			execute( ABILITY, { id: trashed.id } ),
			execute( 'woocommerce/product-update', {
				id: other.id,
				name: 'MCP renamed at the same time',
			} ),
		] );

		if ( result.status === 'fulfilled' ) {
			assert.deepEqual(
				( await lookUp( trashed.id ) ).map( ( { status } ) => status ),
				[ 'trash' ]
			);
		}
	} );
} );
