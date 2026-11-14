import assert from 'node:assert/strict';
import { after, before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/product-update';

const SKU = 'mcp-update';

describe( ABILITY, () => {
	let id;
	before( async () => {
		( { id } = await seed( 'product', { sku: SKU } ) );
	} );
	after( () => seed( 'remove-products', { skus: [ SKU ] } ) );

	test( 'changes only the fields it is given', async () => {
		const { product } = await execute( ABILITY, {
			id,
			name: 'MCP renamed',
		} );

		assert.deepEqual(
			{
				id: product.id,
				name: product.name,
				sku: product.sku,
				regular_price: product.regular_price,
			},
			{ id, name: 'MCP renamed', sku: SKU, regular_price: '10' }
		);
	} );

	test( 'puts the product on sale', async () => {
		const { product } = await execute( ABILITY, { id, sale_price: '7' } );

		assert.equal( product.price, '7' );
	} );

	test( 'takes it off sale with an empty sale price', async () => {
		const { product } = await execute( ABILITY, { id, sale_price: '' } );

		assert.equal( product.price, '10' );
	} );

	test( 'marks it out of stock', async () => {
		const { product } = await execute( ABILITY, {
			id,
			stock_status: 'outofstock',
		} );

		assert.equal( product.stock_status, 'outofstock' );
	} );

	// The ability checks the product's own permission first, so an unknown ID reads as a permission error.
	test( 'refuses an unknown product', () =>
		refused(
			ABILITY,
			{ id: 999999999, name: 'MCP missing' },
			/permission/i
		) );
} );
