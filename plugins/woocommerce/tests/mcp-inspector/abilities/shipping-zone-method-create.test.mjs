import assert from 'node:assert/strict';
import { after, before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/shipping-zone-method-create';

const NAME = 'MCP method create zone';

describe( ABILITY, () => {
	let zone;
	before( async () => {
		zone = await seed( 'shipping-zone', { name: NAME } );
	} );
	after( () => seed( 'remove-shipping-zones', { names: [ NAME ] } ) );

	test( 'adds a flat rate with its settings after the existing method', async () => {
		const { method } = await execute( ABILITY, {
			zone_id: zone.id,
			method_id: 'flat_rate',
			settings: { title: 'MCP courier', cost: '7.5' },
		} );

		assert.deepEqual(
			{ ...method, instance_id: typeof method.instance_id },
			{
				instance_id: 'number',
				method_id: 'flat_rate',
				title: 'MCP courier',
				enabled: true,
				order: 2,
				settings: { tax_status: 'taxable', cost: '7.5' },
			}
		);
	} );

	test( 'adds a disabled free shipping method with a minimum amount', async () => {
		const { method } = await execute( ABILITY, {
			zone_id: zone.id,
			method_id: 'free_shipping',
			enabled: false,
			settings: { requires: 'min_amount', min_amount: '50' },
		} );

		assert.equal( method.enabled, false );
		assert.deepEqual( method.settings, {
			requires: 'min_amount',
			min_amount: '50',
			ignore_discounts: 'no',
		} );
	} );

	test( 'refuses a setting the method does not have', () =>
		refused(
			ABILITY,
			{
				zone_id: zone.id,
				method_id: 'flat_rate',
				settings: { requires: 'coupon' },
			},
			/has no setting requires/
		) );

	test( 'refuses an unknown zone', () =>
		refused(
			ABILITY,
			{ zone_id: 999999999, method_id: 'flat_rate' },
			/no shipping zone with ID 999999999/
		) );
} );
