/**
 * External dependencies
 */
import Currency from '@woocommerce/currency';

/**
 * Internal dependencies
 */
import ShippingRates from './rates';

describe( 'ShippingRates', () => {
	it( 'keeps shipping costs dot-decimal when the currency uses a comma', () => {
		const shippingRates = new ShippingRates( {
			shippingZones: [
				{
					id: 1,
					methods: [
						{
							enabled: true,
							settings: { cost: { value: '12.34' } },
						},
					],
				},
				{ id: 0, methods: [] },
			],
		} );
		shippingRates.context = Currency( {
			decimalSeparator: ',',
			thousandSeparator: '.',
			precision: 2,
		} );

		expect( shippingRates.getFormattedRate( '15.5' ) ).toBe( '15.50' );
		expect( shippingRates.getInitialValues() ).toEqual( {
			'1_rate': '12.34',
			'1_enabled': true,
			'0_rate': '0.00',
			'0_enabled': false,
		} );
	} );
} );
