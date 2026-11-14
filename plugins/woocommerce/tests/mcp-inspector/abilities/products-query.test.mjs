import assert from 'node:assert/strict';
import { before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/products-query';

const idsOf = ( { products } ) => products.map( ( { id } ) => id ).sort();

describe( ABILITY, () => {
	let catalog;
	before( async () => {
		catalog = await seed( 'products' );
	} );

	// Searching for the fixtures' SKU prefix keeps other products in a long-lived dev environment out of the results.
	const query = ( parameters ) =>
		execute( ABILITY, {
			search: 'mcp-catalog',
			per_page: 100,
			...parameters,
		} );

	test( 'finds a product by its SKU', async () => {
		const data = await execute( ABILITY, { sku: 'mcp-catalog-instock' } );

		assert.deepEqual( idsOf( data ), [ catalog.instock.id ] );
	} );

	test( 'includes drafts unless a status is given', async () => {
		assert.deepEqual(
			idsOf( await query( {} ) ),
			Object.values( catalog )
				.map( ( { id } ) => id )
				.sort()
		);
		assert.deepEqual( idsOf( await query( { status: 'draft' } ) ), [
			catalog.draft.id,
		] );
	} );

	for ( const [ title, parameters, key ] of [
		[
			'filters by stock status',
			{ stock_status: 'outofstock' },
			'outofstock',
		],
		[ 'filters virtual products', { virtual: true }, 'virtual' ],
	] ) {
		test( title, async () => {
			assert.deepEqual( idsOf( await query( parameters ) ), [
				catalog[ key ].id,
			] );
		} );
	}

	test( 'pages through the results', async () => {
		const data = await query( { per_page: 3, page: 2 } );

		assert.equal( data.products.length, 1 );
		assert.deepEqual(
			{
				page: data.page,
				per_page: data.per_page,
				total_pages: data.total_pages,
			},
			{ page: 2, per_page: 3, total_pages: 2 }
		);
	} );

	test( 'refuses an unknown status', () =>
		refused( ABILITY, { status: 'archived' }, /input\[status\]/ ) );
} );
