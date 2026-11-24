import assert from 'node:assert/strict';
import { describe, test } from 'node:test';
import { execute } from '../lib/abilities.mjs';

const ABILITY = 'woocommerce/store-settings-get';

const settingOf = async ( group, id ) =>
	(
		await execute( 'woocommerce/settings-query', {
			group,
			include: [ id ],
		} )
	).settings[ 0 ].value;

describe( ABILITY, () => {
	test( 'returns the store location, currency and units as the settings hold them', async () => {
		const store = await execute( ABILITY, {} );
		const [ country, state ] = (
			await settingOf( 'general', 'woocommerce_default_country' )
		).split( ':' );

		assert.deepEqual(
			{
				country_code: store.address.country_code,
				state_code: store.address.state_code,
				currency: store.currency.code,
				weight: store.units.weight,
			},
			{
				country_code: country,
				state_code: state,
				currency: await settingOf( 'general', 'woocommerce_currency' ),
				weight: await settingOf(
					'products',
					'woocommerce_weight_unit'
				),
			}
		);
	} );

	test( 'formats prices as the store does', async () => {
		assert.deepEqual( ( await execute( ABILITY, {} ) ).currency, {
			code: 'USD',
			symbol: '$',
			precision: 2,
			symbol_position: 'left',
			decimal_separator: '.',
			thousand_separator: ',',
			price_format: '%1$s%2$s',
		} );
	} );

	test( 'returns the WooCommerce version that system-status-get reports', async () => {
		const { system_status: status } = await execute(
			'woocommerce/system-status-get',
			{}
		);

		assert.equal(
			( await execute( ABILITY, {} ) ).woocommerce_version,
			status.woocommerce_version
		);
	} );
} );
