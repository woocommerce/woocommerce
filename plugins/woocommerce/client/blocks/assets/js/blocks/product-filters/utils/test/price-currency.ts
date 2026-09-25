import type { Currency } from '@woocommerce/types';
import { formatPrice } from '../price-currency';

jest.mock( '@wordpress/interactivity', () => ( {
	getConfig: () => ( {
		currency: {
			code: 'USD',
			symbol: '$',
			symbolPosition: 'left',
			minorUnit: 0,
			thousandSeparator: ',',
			decimalSeparator: '.',
		},
	} ),
} ) );

describe( 'product filter price formatting', () => {
	it( 'isolates an RTL prefix without changing Latin currency output', () => {
		const currency: Currency = {
			code: 'LBP',
			symbol: 'ل.ل',
			prefix: 'ل.ل\u00a0',
			suffix: '',
			minorUnit: 0,
			thousandSeparator: ',',
			decimalSeparator: '.',
		};

		expect( formatPrice( 1234, currency ) ).toBe(
			'\u2068ل.ل\u2069\u00a01,234'
		);
		expect(
			formatPrice( 1234, { ...currency, prefix: '', suffix: ' ل.ل' } )
		).toBe( '1,234 \u2068ل.ل\u2069' );
		expect( formatPrice( 1234, { ...currency, prefix: '$\u00a0' } ) ).toBe(
			'$\u00a01,234'
		);
	} );
} );
