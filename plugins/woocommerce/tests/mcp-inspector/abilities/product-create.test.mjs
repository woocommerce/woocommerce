import assert from 'node:assert/strict';
import { after, before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/product-create';

const SKUS = [ 'mcp-create-simple', 'mcp-create-sale', 'mcp-create-draft' ];

describe( ABILITY, () => {
	// Before as well as after, so a run that failed halfway leaves no SKU behind to collide with.
	const removeCreated = () => seed( 'remove-products', { skus: SKUS } );
	before( removeCreated );
	after( removeCreated );

	test( 'creates a published simple product by default', async () => {
		const { product } = await execute( ABILITY, {
			name: 'MCP create simple',
			sku: 'mcp-create-simple',
			regular_price: '12.50',
		} );

		assert.ok( product.id > 0 );
		assert.deepEqual(
			{
				name: product.name,
				sku: product.sku,
				type: product.type,
				status: product.status,
				price: product.price,
			},
			{
				name: 'MCP create simple',
				sku: 'mcp-create-simple',
				type: 'simple',
				status: 'publish',
				price: '12.50',
			}
		);
	} );

	test( 'sells at the sale price', async () => {
		const { product } = await execute( ABILITY, {
			name: 'MCP create sale',
			sku: 'mcp-create-sale',
			regular_price: '20',
			sale_price: '15',
		} );

		assert.equal( product.price, '15' );
		assert.equal( product.regular_price, '20' );
	} );

	test( 'creates a draft when asked', async () => {
		const { product } = await execute( ABILITY, {
			name: 'MCP create draft',
			sku: 'mcp-create-draft',
			status: 'draft',
		} );

		assert.equal( product.status, 'draft' );
	} );

	test( 'refuses a SKU another product has', () =>
		refused(
			ABILITY,
			{ name: 'MCP create duplicate', sku: 'mcp-create-simple' },
			/duplicated SKU/
		) );

	for ( const [ title, parameters, pattern ] of [
		[
			'refuses a product without a name',
			{ sku: 'mcp-create-unnamed' },
			/needs a name/,
		],
		[
			'refuses a price that is not a number',
			{ name: 'MCP create', regular_price: 'ten' },
			/input\[regular_price\]/,
		],
		[
			'refuses an unknown product type',
			{ name: 'MCP create', type: 'bundle' },
			/input\[type\]/,
		],
	] ) {
		test( title, () => refused( ABILITY, parameters, pattern ) );
	}
} );
