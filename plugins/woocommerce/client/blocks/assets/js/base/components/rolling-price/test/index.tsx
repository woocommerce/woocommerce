/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import type { Currency } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import RollingPrice from '..';

const currency: Currency = {
	code: 'USD',
	symbol: '$',
	thousandSeparator: ',',
	decimalSeparator: '.',
	minorUnit: 2,
	prefix: '$',
	suffix: '',
};

describe( 'RollingPrice', () => {
	it( 'has the same text as a plain price, and keeps it current', () => {
		const { container, rerender } = render(
			<RollingPrice value={ 10476 } currency={ currency } />
		);

		expect( container.textContent ).toBe( '$104.76' );

		rerender( <RollingPrice value={ 115556 } currency={ currency } /> );

		expect( container.textContent ).toBe( '$1,155.56' );
	} );

	it( 'puts the minus sign before the currency symbol, as a plain price does', () => {
		const { container } = render(
			<RollingPrice value={ -1000 } currency={ currency } />
		);

		expect( container.textContent ).toBe( '-$10.00' );
	} );
} );
