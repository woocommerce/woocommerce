import assert from 'node:assert/strict';
import { describe, test } from 'node:test';
import { execute, refused } from '../lib/abilities.mjs';

const ABILITY = 'woocommerce/settings-query';

describe( ABILITY, () => {
	test( 'returns a setting with its value, default and choices', async () => {
		const { settings } = await execute( ABILITY, {
			group: 'general',
			include: [ 'woocommerce_allowed_countries' ],
		} );

		assert.deepEqual( settings, [
			{
				id: 'woocommerce_allowed_countries',
				label: 'Selling location(s)',
				description:
					'This option lets you limit which countries you are willing to sell to.',
				type: 'select',
				value: 'all',
				default: 'all',
				options: {
					all: 'Sell to all countries',
					all_except: 'Sell to all countries, except for&hellip;',
					specific: 'Sell to specific countries',
				},
			},
		] );
	} );

	test( 'returns a country field without its choices', async () => {
		const [ setting ] = (
			await execute( ABILITY, {
				group: 'general',
				include: [ 'woocommerce_default_country' ],
			} )
		).settings;

		assert.equal( setting.type, 'single_select_country' );
		assert.equal( setting.options, undefined );
	} );

	test( 'leaves out the fields that are not opted into ability reads, and names them', async () => {
		const data = await execute( ABILITY, {
			group: 'advanced',
			per_page: 100,
		} );

		assert.ok( data.omitted.includes( 'woocommerce_analytics_enabled' ) );
		assert.ok(
			! data.settings.some(
				( { id } ) => id === 'woocommerce_analytics_enabled'
			)
		);
	} );

	test( 'pages through a group', async () => {
		const data = await execute( ABILITY, {
			group: 'general',
			per_page: 2,
		} );

		assert.deepEqual(
			data.settings.map( ( { id } ) => id ),
			[ 'woocommerce_store_address', 'woocommerce_store_address_2' ]
		);
		assert.equal( data.total_pages, Math.ceil( data.total / 2 ) );
	} );

	test( 'refuses an unknown group', () =>
		refused(
			ABILITY,
			{ group: 'mcp-missing' },
			/Settings group not found/
		) );
} );
