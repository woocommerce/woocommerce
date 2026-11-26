import assert from 'node:assert/strict';
import { after, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';

const ABILITY = 'woocommerce/settings-update';

const ADDRESS = 'woocommerce_store_address_2';
const SHIP_TO = 'woocommerce_specific_ship_to_countries';

const valuesOf = async () =>
	Object.fromEntries(
		(
			await execute( 'woocommerce/settings-query', {
				group: 'general',
				include: [ ADDRESS, SHIP_TO ],
			} )
		).settings.map( ( { id, value } ) => [ id, value ] )
	);

// The tests run in order. Neither setting changes what the other tests see: the store sells and ships to all countries.
describe( ABILITY, () => {
	after( () =>
		execute( ABILITY, {
			group: 'general',
			settings: [
				{ id: ADDRESS, value: '' },
				{ id: SHIP_TO, values: [] },
			],
		} )
	);

	test( 'changes a text and a list setting, and returns their previous values', async () => {
		await execute( ABILITY, {
			group: 'general',
			settings: [
				{ id: ADDRESS, value: 'MCP floor 1' },
				{ id: SHIP_TO, values: [ 'DE' ] },
			],
		} );

		assert.deepEqual(
			await execute( ABILITY, {
				group: 'general',
				settings: [
					{ id: ADDRESS, value: 'MCP floor 2' },
					{ id: SHIP_TO, values: [ 'DE', 'FR' ] },
				],
			} ),
			{
				settings: [
					{
						id: ADDRESS,
						previous: 'MCP floor 1',
						value: 'MCP floor 2',
					},
					{ id: SHIP_TO, previous: [ 'DE' ], value: [ 'DE', 'FR' ] },
				],
			}
		);
		assert.deepEqual( await valuesOf(), {
			[ ADDRESS ]: 'MCP floor 2',
			[ SHIP_TO ]: [ 'DE', 'FR' ],
		} );
	} );

	test( 'saves nothing when one setting has an invalid value', async () => {
		await refused(
			ABILITY,
			{
				group: 'general',
				settings: [
					{ id: ADDRESS, value: 'MCP floor 3' },
					{
						id: 'woocommerce_allowed_countries',
						value: 'mcp-nowhere',
					},
				],
			},
			/invalid values: woocommerce_allowed_countries\. Nothing was saved\./
		);
		assert.equal( ( await valuesOf() )[ ADDRESS ], 'MCP floor 2' );
	} );

	test( 'refuses a country that does not exist', () =>
		refused(
			ABILITY,
			{
				group: 'general',
				settings: [ { id: SHIP_TO, values: [ 'XX' ] } ],
			},
			new RegExp( `invalid values: ${ SHIP_TO }` )
		) );

	test( 'refuses a setting that is not opted into ability writes', () =>
		refused(
			ABILITY,
			{
				group: 'advanced',
				settings: [
					{ id: 'woocommerce_analytics_enabled', value: 'no' },
				],
			},
			/cannot be changed: woocommerce_analytics_enabled/
		) );

	test( 'refuses a setting with neither a value nor values', () =>
		refused(
			ABILITY,
			{ group: 'general', settings: [ { id: ADDRESS } ] },
			/exactly one of value or values/
		) );

	test( 'refuses an unknown group', () =>
		refused(
			ABILITY,
			{ group: 'mcp-missing', settings: [ { id: ADDRESS, value: '' } ] },
			/Settings group not found/
		) );
} );
