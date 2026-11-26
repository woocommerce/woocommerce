import assert from 'node:assert/strict';
import { after, before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/coupon-create';

const CODES = [
	'mcp-create-default',
	'mcp-create-percent',
	'mcp-create-taken',
];

describe( ABILITY, () => {
	before( async () => {
		await seed( 'remove-coupons', { codes: CODES } );
		await seed( 'coupon', { code: CODES[ 2 ] } );
	} );
	after( () => seed( 'remove-coupons', { codes: CODES } ) );

	test( 'creates a published fixed cart coupon by default', async () => {
		const { coupon } = await execute( ABILITY, {
			code: CODES[ 0 ],
			amount: '5',
		} );

		assert.deepEqual(
			{
				code: coupon.code,
				status: coupon.status,
				discount_type: coupon.discount_type,
				amount: coupon.amount,
				usage_limit: coupon.usage_limit,
				redeemable: coupon.redeemable,
			},
			{
				code: CODES[ 0 ],
				status: 'publish',
				discount_type: 'fixed_cart',
				amount: '5',
				usage_limit: 0,
				redeemable: true,
			}
		);
		assert.equal( coupon.date_expires, undefined );
	} );

	test( 'creates a percent coupon with limits and an expiry date', async () => {
		const { coupon } = await execute( ABILITY, {
			code: CODES[ 1 ],
			discount_type: 'percent',
			amount: '15',
			date_expires: '2030-01-01',
			usage_limit: 5,
			usage_limit_per_user: 1,
			email_restrictions: [ '*@fixtures.mcp.test' ],
		} );

		assert.deepEqual(
			{
				discount_type: coupon.discount_type,
				amount: coupon.amount,
				usage_limit: coupon.usage_limit,
				usage_limit_per_user: coupon.usage_limit_per_user,
				email_restrictions: coupon.email_restrictions,
				date_expires: coupon.date_expires,
				last_valid_day: coupon.expiry.last_valid_day,
			},
			{
				discount_type: 'percent',
				amount: '15',
				usage_limit: 5,
				usage_limit_per_user: 1,
				email_restrictions: [ '*@fixtures.mcp.test' ],
				date_expires: '2030-01-01T00:00:00+00:00',
				last_valid_day: '2029-12-31',
			}
		);
	} );

	test( 'refuses a code another coupon has, in any case', () =>
		refused(
			ABILITY,
			{ code: CODES[ 2 ].toUpperCase() },
			/already exists/
		) );

	test( 'refuses a percent discount over 100', () =>
		refused(
			ABILITY,
			{
				code: 'mcp-create-too-much',
				discount_type: 'percent',
				amount: '150',
			},
			/Invalid discount amount/
		) );

	test( 'refuses an amount with a currency symbol', () =>
		refused(
			ABILITY,
			{ code: 'mcp-create-symbol', amount: '$5' },
			/input\[amount\]/
		) );
} );
