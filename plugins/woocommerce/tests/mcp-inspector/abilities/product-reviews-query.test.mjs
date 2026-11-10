import assert from 'node:assert/strict';
import { before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/product-reviews-query';

describe( ABILITY, () => {
	let catalog;
	before( async () => {
		catalog = await seed( 'reviews' );
	} );

	const query = ( parameters = {} ) =>
		execute( ABILITY, {
			product_ids: [ catalog.loved, catalog.panned ],
			...parameters,
		} );
	const idsOf = ( { reviews } ) => reviews.map( ( { id } ) => id );

	test( 'lists the approved reviews, newest first', async () => {
		const { reviews } = await query();

		assert.deepEqual( idsOf( { reviews } ), [
			catalog.reviews[ 'panned-two' ],
			catalog.reviews[ 'loved-three' ],
			catalog.reviews[ 'loved-five' ],
		] );
		assert.deepEqual(
			reviews.map( ( { rating, review, product_id } ) => ( {
				rating,
				review,
				product_id,
			} ) )[ 0 ],
			{
				rating: 2,
				review: 'Arrived scratched',
				product_id: catalog.panned,
			}
		);
	} );

	test( 'ranks the products from the lowest average rating', async () => {
		const { by_product: byProduct, summary } = await query();

		assert.deepEqual(
			byProduct.map(
				( {
					product_id: id,
					review_count: count,
					average_rating,
				} ) => ( {
					id,
					count,
					average_rating,
				} )
			),
			[
				{ id: catalog.panned, count: 1, average_rating: 2 },
				{ id: catalog.loved, count: 2, average_rating: 4 },
			]
		);
		assert.deepEqual( summary, {
			average_rating: 3.33,
			total_reviews: 3,
			matching_reviews: 3,
			total_products: 2,
		} );
	} );

	test( 'leaves the product out of each review for one product', async () => {
		const { reviews } = await query( { product_ids: [ catalog.loved ] } );

		assert.equal( reviews.length, 2 );
		assert.ok(
			reviews.every( ( review ) => ! ( 'product_id' in review ) )
		);
	} );

	test( 'filters by rating', async () => {
		assert.deepEqual( idsOf( await query( { min_rating: 3 } ) ), [
			catalog.reviews[ 'loved-three' ],
			catalog.reviews[ 'loved-five' ],
		] );
		assert.deepEqual( idsOf( await query( { max_rating: 2 } ) ), [
			catalog.reviews[ 'panned-two' ],
		] );
	} );

	test( 'lists held reviews only when asked', async () => {
		assert.deepEqual( idsOf( await query( { status: 'hold' } ) ), [
			catalog.reviews[ 'loved-held' ],
		] );

		const { reviews } = await query( { status: 'all' } );
		assert.deepEqual(
			reviews.map( ( { status } ) => status ),
			[ 'hold', 'approved', 'approved', 'approved' ]
		);
	} );

	test( 'searches the review text only', async () => {
		const data = await query( { search: 'strap' } );

		assert.deepEqual( idsOf( data ), [ catalog.reviews[ 'loved-three' ] ] );
		assert.equal( data.search_scope, 'review_text' );
		assert.deepEqual(
			idsOf( await query( { search: 'MCP reviewer' } ) ),
			[]
		);
	} );

	test( 'tells products without reviews from unknown IDs', async () => {
		const data = await execute( ABILITY, {
			product_ids: [ catalog.loved, catalog.unreviewed, 999999999 ],
		} );

		assert.deepEqual( data.products_without_reviews, [
			catalog.unreviewed,
		] );
		assert.deepEqual( data.unknown_product_ids, [ 999999999 ] );
	} );

	test( 'pages through the reviews', async () => {
		const data = await query( { per_page: 2, page: 2 } );

		assert.deepEqual( idsOf( data ), [ catalog.reviews[ 'loved-five' ] ] );
		assert.deepEqual(
			{
				total: data.total,
				total_pages: data.total_pages,
				page: data.page,
			},
			{ total: 3, total_pages: 2, page: 2 }
		);
	} );

	test( 'refuses a minimum rating above the maximum', () =>
		refused(
			ABILITY,
			{ min_rating: 4, max_rating: 2 },
			/min_rating must not exceed max_rating/
		) );
} );
