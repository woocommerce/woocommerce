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
			deleted: true,
			id,
		} );
		assert.deepEqual(
			( await lookUp( id ) ).map( ( { status } ) => status ),
			[ 'trash' ]
		);
	} );

	test( 'deletes it for good with force', async () => {
		assert.deepEqual( await execute( ABILITY, { id, force: true } ), {
			deleted: true,
			id,
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
