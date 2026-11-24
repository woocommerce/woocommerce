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

	test( 'refuses an unknown product', () =>
		refused(
			ABILITY,
			{ id: 999999999, name: 'MCP missing' },
			/Product not found/
		) );
} );

describe( `${ ABILITY } with a GTIN`, () => {
	const SKUS = [ 'mcp-update-gtin', 'mcp-update-gtin-other' ];
	const GTIN = '9990000000011';
	let id;
	let other;
	before( async () => {
		await seed( 'remove-products', { skus: SKUS } );
		( { id } = await seed( 'product', { sku: SKUS[ 0 ] } ) );
		( { id: other } = await seed( 'product', { sku: SKUS[ 1 ] } ) );
	} );
	after( () => seed( 'remove-products', { skus: SKUS } ) );

	const findByGtin = async () =>
		(
			await execute( 'woocommerce/products-query', {
				global_unique_id: [ GTIN ],
			} )
		).products.map( ( product ) => product.id );

	test( 'sets a GTIN that products-query finds', async () => {
		const { product } = await execute( ABILITY, {
			id,
			global_unique_id: GTIN,
		} );

		assert.equal( product.global_unique_id, GTIN );
		assert.deepEqual( await findByGtin(), [ id ] );
	} );

	test( 'refuses a GTIN another product has', () =>
		refused(
			ABILITY,
			{ id: other, global_unique_id: GTIN },
			/duplicated GTIN/
		) );

	test( 'refuses a GTIN with letters', () =>
		refused(
			ABILITY,
			{ id, global_unique_id: 'abc' },
			/input\[global_unique_id\]/
		) );

	test( 'removes the GTIN with an empty string', async () => {
		const { product } = await execute( ABILITY, {
			id,
			global_unique_id: '',
		} );

		assert.equal( product.global_unique_id, '' );
		assert.deepEqual( await findByGtin(), [] );
	} );
} );
