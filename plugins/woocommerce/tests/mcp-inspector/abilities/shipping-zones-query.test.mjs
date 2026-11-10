import assert from 'node:assert/strict';
import { before, describe, test } from 'node:test';
import { execute } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/shipping-zones-query';

describe( ABILITY, () => {
	let zone;
	before( async () => {
		zone = await seed( 'shipping-zone' );
	} );

	test( 'returns a zone with its locations and method settings', async () => {
		const { zones } = await execute( ABILITY, { include: [ zone.id ] } );

		assert.deepEqual(
			zones.map( ( { id, name, locations, methods } ) => ( {
				id,
				name,
				locations,
				methods: methods.map(
					( {
						instance_id,
						method_id,
						title,
						enabled,
						settings,
					} ) => ( {
						instance_id,
						method_id,
						title,
						enabled,
						cost: settings.cost,
					} )
				),
			} ) ),
			[
				{
					id: zone.id,
					name: zone.name,
					locations: [ { code: 'DE', type: 'country' } ],
					methods: [
						{
							instance_id: zone.flat_rate,
							method_id: 'flat_rate',
							title: 'MCP flat rate',
							enabled: true,
							cost: '5',
						},
					],
				},
			]
		);
	} );

	test( 'lists the zone for locations no other zone covers last', async () => {
		const { zones } = await execute( ABILITY );

		assert.ok( zones.some( ( { id } ) => id === zone.id ) );
		assert.equal( zones.at( -1 ).id, 0 );
		assert.deepEqual( zones.at( -1 ).locations, [] );
	} );

	test( 'returns nothing for an unknown ID', async () => {
		assert.deepEqual(
			( await execute( ABILITY, { include: [ 999999999 ] } ) ).zones,
			[]
		);
	} );
} );
