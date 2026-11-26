/**
 * Internal dependencies
 */
import { getShippingMethodElements } from '../enable-for-methods/field';
import { getField as getEnableForVirtual } from '../enable-for-virtual/field';
import type { OfflineGatewaySettings } from '../types';

const record = (
	settings: OfflineGatewaySettings[ 'settings' ]
): OfflineGatewaySettings => ( {
	id: 'cod',
	enabled: true,
	title: '',
	description: '',
	settings,
} );

describe( 'getShippingMethodElements', () => {
	it( 'lists every rate across the method groups with decoded labels', () => {
		expect(
			getShippingMethodElements( {
				'Flat rate': {
					flat_rate: 'Any &quot;Flat rate&quot; method',
					'flat_rate:3': 'UK &ndash; Flat rate (#3)',
				},
				'Free shipping': { free_shipping: 'Any free shipping' },
			} )
		).toEqual( [
			{ value: 'flat_rate', label: 'Any "Flat rate" method' },
			{ value: 'flat_rate:3', label: 'UK – Flat rate (#3)' },
			{ value: 'free_shipping', label: 'Any free shipping' },
		] );
	} );
} );

describe( 'enable_for_virtual field', () => {
	const field = getEnableForVirtual( 'cash on delivery' );

	it.each( [
		[ 'a saved yes', { value: 'yes' }, true ],
		[ 'a saved no', { value: 'no' }, false ],
		[ 'an edited no', 'no', false ],
		[ 'no saved value, like the gateway default', undefined, true ],
	] )( 'reads %s', ( _label, setting, expected ) => {
		expect(
			field.getValue?.( {
				item: record(
					setting === undefined ? {} : { enable_for_virtual: setting }
				),
			} )
		).toBe( expected );
	} );

	it( 'saves the yes/no string, with the other settings as plain values', () => {
		expect(
			field.setValue?.( {
				item: record( {
					instructions: { value: 'Pay on delivery' },
					enable_for_methods: [ 'flat_rate' ],
				} ),
				value: false,
			} )
		).toEqual( {
			settings: {
				instructions: 'Pay on delivery',
				enable_for_methods: [ 'flat_rate' ],
				enable_for_virtual: 'no',
			},
		} );
	} );
} );
