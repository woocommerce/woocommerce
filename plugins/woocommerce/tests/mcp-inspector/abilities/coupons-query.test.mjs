import assert from 'node:assert/strict';
import { after, before, describe, test } from 'node:test';
import { execute } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/coupons-query';

const CODES = [ 'mcp-query-ten', 'mcp-query-draft' ];

describe( ABILITY, () => {
	let ten;
	let draft;
	before( async () => {
		await seed( 'remove-coupons', { codes: CODES } );
		ten = await seed( 'coupon', { code: CODES[ 0 ] } );
		draft = await seed( 'coupon', { code: CODES[ 1 ] } );
		await execute( 'woocommerce/coupon-update', {
			id: draft.id,
			status: 'draft',
			discount_type: 'fixed_cart',
		} );
	} );
	after( () => seed( 'remove-coupons', { codes: CODES } ) );

	const idsOf = ( data ) => data.coupons.map( ( { id } ) => id );
	const query = ( parameters = {} ) =>
		execute( ABILITY, { include: [ ten.id, draft.id ], ...parameters } );

	test( 'finds a coupon by its exact code, in any case', async () => {
		const { coupons } = await execute( ABILITY, {
			code: CODES[ 0 ].toUpperCase(),
		} );

		assert.deepEqual(
			coupons.map(
				( { id, code, status, discount_type: type, amount } ) => ( {
					id,
					code,
					status,
					discount_type: type,
					amount,
				} )
			),
			[
				{
					id: ten.id,
					code: CODES[ 0 ],
					status: 'publish',
					discount_type: 'percent',
					amount: '10',
				},
			]
		);
	} );

	test( 'finds a draft by its code too', async () => {
		assert.deepEqual(
			idsOf( await execute( ABILITY, { code: CODES[ 1 ] } ) ),
			[ draft.id ]
		);
	} );

	test( 'filters by discount type and status', async () => {
		assert.deepEqual(
			idsOf( await query( { discount_type: 'percent' } ) ),
			[ ten.id ]
		);
		assert.deepEqual( idsOf( await query( { status: [ 'draft' ] } ) ), [
			draft.id,
		] );
	} );

	test( 'searches the codes', async () => {
		assert.deepEqual(
			idsOf( await execute( ABILITY, { search: 'mcp-query-dr' } ) ),
			[ draft.id ]
		);
	} );

	test( 'returns nothing for a code no coupon has', async () => {
		assert.deepEqual(
			await execute( ABILITY, { code: 'mcp-query-missing' } ),
			{ coupons: [], total: 0, total_pages: 0, page: 1, per_page: 10 }
		);
	} );
} );
