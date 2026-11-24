import assert from 'node:assert/strict';
import { after, before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/coupon-update';

const CODES = [ 'mcp-update', 'mcp-update-renamed', 'mcp-update-other' ];

// The tests run in order and change the same coupon.
describe( ABILITY, () => {
	let id;
	before( async () => {
		await seed( 'remove-coupons', { codes: CODES } );
		( { id } = await seed( 'coupon', { code: CODES[ 0 ] } ) );
		await seed( 'coupon', { code: CODES[ 2 ] } );
	} );
	after( () => seed( 'remove-coupons', { codes: CODES } ) );

	test( 'changes only the fields it is given', async () => {
		const { coupon } = await execute( ABILITY, {
			id,
			amount: '20',
			date_expires: '2030-06-01',
		} );

		assert.deepEqual(
			{
				code: coupon.code,
				discount_type: coupon.discount_type,
				amount: coupon.amount,
				date_expires: coupon.date_expires,
			},
			{
				code: CODES[ 0 ],
				discount_type: 'percent',
				amount: '20',
				date_expires: '2030-06-01T00:00:00+00:00',
			}
		);
	} );

	test( 'removes the expiry date with an empty string', async () => {
		const { coupon } = await execute( ABILITY, { id, date_expires: '' } );

		assert.equal( coupon.date_expires, undefined );
	} );

	test( 'renames the coupon', async () => {
		const { coupon } = await execute( ABILITY, { id, code: CODES[ 1 ] } );

		assert.equal( coupon.code, CODES[ 1 ] );
	} );

	test( 'refuses a code another coupon has', () =>
		refused( ABILITY, { id, code: CODES[ 2 ] }, /already exists/ ) );

	test( 'refuses a maximum spend below the minimum', () =>
		refused(
			ABILITY,
			{ id, minimum_amount: '50', maximum_amount: '10' },
			/Invalid maximum spend/
		) );

	test( 'refuses an email restriction that is not an email', () =>
		refused(
			ABILITY,
			{ id, email_restrictions: [ 'not an email' ] },
			/valid email addresses/
		) );

	test( 'refuses to schedule a coupon without a publication date', () =>
		refused(
			ABILITY,
			{ id, status: 'future' },
			/future publication date/
		) );

	// The permission check reads the coupon, so an unknown ID reads as a permission error.
	test( 'refuses an unknown coupon', () =>
		refused( ABILITY, { id: 999999999, amount: '1' }, /permission/i ) );
} );
