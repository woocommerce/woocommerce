import assert from 'node:assert/strict';
import { after, before, describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';
import { seed } from '../lib/fixtures.mjs';

const ABILITY = 'woocommerce/tax-rate-create';

const NAMES = [ 'MCP created FR', 'MCP created defaults' ];

// Taxes are off in the environment, so the rates change no order total of the other tests.
describe( ABILITY, () => {
	const remove = () => seed( 'remove-tax-rates', { names: NAMES } );
	before( remove );
	after( remove );

	test( 'creates a rate with its locations, and upper-cases the codes', async () => {
		const { rate } = await execute( ABILITY, {
			rate: '20',
			country: 'fr',
			postcodes: [ '75*', '69000...69999' ],
			cities: [ 'Paris' ],
			name: NAMES[ 0 ],
			priority: 2,
			shipping: false,
			class: 'reduced-rate',
		} );

		assert.deepEqual(
			{ ...rate, id: typeof rate.id },
			{
				id: 'number',
				country: 'FR',
				state: '',
				postcodes: [ '75*', '69000...69999' ],
				cities: [ 'PARIS' ],
				rate: '20.0000',
				name: NAMES[ 0 ],
				priority: 2,
				compound: false,
				shipping: false,
				class: 'reduced-rate',
			}
		);
		assert.deepEqual(
			(
				await execute( 'woocommerce/tax-rates-query', {
					include: [ rate.id ],
				} )
			).rates,
			[ rate ]
		);
	} );

	test( 'creates a standard rate for every location by default', async () => {
		const { rate } = await execute( ABILITY, {
			rate: '7.25',
			name: NAMES[ 1 ],
		} );

		assert.deepEqual(
			{ ...rate, id: undefined },
			{
				id: undefined,
				country: '',
				state: '',
				postcodes: [],
				cities: [],
				rate: '7.2500',
				name: NAMES[ 1 ],
				priority: 1,
				compound: false,
				shipping: true,
				class: 'standard',
			}
		);
	} );

	test( 'refuses an unknown tax class, and lists the classes', () =>
		refused(
			ABILITY,
			{ rate: '5', class: 'mcp-missing' },
			/There is no tax class mcp-missing\. Use one of: standard, reduced-rate, zero-rate\./
		) );

	test( 'refuses a country that does not exist', () =>
		refused(
			ABILITY,
			{ rate: '5', country: 'XX' },
			/"XX" is not a valid country/
		) );

	test( 'refuses a state the country does not have', () =>
		refused(
			ABILITY,
			{ rate: '5', country: 'US', state: 'ZZ' },
			/"ZZ" is not a state of US/
		) );

	test( 'refuses a rate that is not a percentage', () =>
		refused( ABILITY, { rate: '19%' }, /input\[rate\]/ ) );
} );
