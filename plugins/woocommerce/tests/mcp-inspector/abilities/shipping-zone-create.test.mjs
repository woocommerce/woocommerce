import assert from 'node:assert/strict';
import { after, before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/shipping-zone-create';

const NAMES = [ 'MCP created zone' ];

describe( ABILITY, () => {
	before( () => seed( 'remove-shipping-zones', { names: NAMES } ) );
	after( () => seed( 'remove-shipping-zones', { names: NAMES } ) );

	test( 'creates a zone without methods for its locations', async () => {
		const { zone } = await execute( ABILITY, {
			name: NAMES[ 0 ],
			locations: [
				{ code: 'FR', type: 'country' },
				{ code: '75*', type: 'postcode' },
			],
			order: 3,
		} );

		assert.deepEqual(
			{ ...zone, id: typeof zone.id },
			{
				id: 'number',
				name: NAMES[ 0 ],
				order: 3,
				locations: [
					{ code: 'FR', type: 'country' },
					{ code: '75*', type: 'postcode' },
				],
				methods: [],
			}
		);
		assert.deepEqual(
			(
				await execute( 'woocommerce/shipping-zones-query', {
					include: [ zone.id ],
				} )
			).zones.map( ( { name } ) => name ),
			[ NAMES[ 0 ] ]
		);
	} );

	test( 'refuses a zone without locations', () =>
		refused(
			ABILITY,
			{ name: 'MCP empty zone', locations: [] },
			/input\[locations\]/
		) );
} );
