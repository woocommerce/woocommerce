import assert from 'node:assert/strict';
import { after, before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/shipping-zone-method-update';

const NAME = 'MCP method update zone';

describe( ABILITY, () => {
	let zone;
	before( async () => {
		zone = await seed( 'shipping-zone', { name: NAME } );
	} );
	after( () => seed( 'remove-shipping-zones', { names: [ NAME ] } ) );

	test( 'changes only the settings it is given', async () => {
		const { method } = await execute( ABILITY, {
			instance_id: zone.flat_rate,
			settings: { cost: '[qty] * 2' },
		} );

		assert.equal( method.title, 'MCP flat rate' );
		assert.deepEqual( method.settings, {
			tax_status: 'taxable',
			cost: '[qty] * 2',
		} );
	} );

	test( 'turns the method off', async () => {
		const { method } = await execute( ABILITY, {
			instance_id: zone.flat_rate,
			enabled: false,
		} );

		assert.equal( method.enabled, false );
		assert.equal(
			(
				await execute( 'woocommerce/shipping-zones-query', {
					include: [ zone.id ],
				} )
			).zones[ 0 ].methods[ 0 ].enabled,
			false
		);
	} );

	// The settings screen's price validator would save text without digits as an empty minimum, which every cart meets.
	test( 'refuses a minimum amount that is not a number', async () => {
		const { method } = await execute(
			'woocommerce/shipping-zone-method-create',
			{ zone_id: zone.id, method_id: 'free_shipping' }
		);

		await refused(
			ABILITY,
			{
				instance_id: method.instance_id,
				settings: { requires: 'min_amount', min_amount: 'abc' },
			},
			/must be a number/
		);
	} );

	test( 'refuses an unknown method', () =>
		refused(
			ABILITY,
			{ instance_id: 999999999, enabled: false },
			/no shipping method with instance ID 999999999/
		) );
} );
