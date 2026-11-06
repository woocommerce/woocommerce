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
	it( 'exposes only the formatted price to assistive technology, and keeps it current', () => {
		const { container, rerender } = render(
			<RollingPrice value={ 10476 } currency={ currency } />
		);
		const accessibleText = () =>
			[ ...container.querySelectorAll( '*' ) ]
				.filter(
					( element ) =>
						! element.closest( '[aria-hidden="true"]' ) &&
						element.children.length === 0
				)
				.map( ( element ) => element.textContent )
				.join( '' );

		expect( accessibleText() ).toBe( '$104.76' );

		rerender( <RollingPrice value={ 115556 } currency={ currency } /> );

		expect( accessibleText() ).toBe( '$1,155.56' );
	} );

	it( 'keeps the letters of a currency symbol together, so scripts that join letters still join', () => {
		const { container } = render(
			<RollingPrice
				value={ 10476 }
				currency={ {
					...currency,
					code: 'SAR',
					symbol: 'ريال',
					prefix: '',
					suffix: ' ريال',
				} }
			/>
		);
		const pieces = [
			...container.querySelectorAll(
				'.wc-block-components-rolling-price__characters > span'
			),
		].map( ( piece ) =>
			piece.classList.contains(
				'wc-block-components-rolling-price__digit'
			)
				? 'digit'
				: piece.textContent
		);

		expect( pieces ).toEqual( [
			'digit',
			'digit',
			'digit',
			'.',
			'digit',
			'digit',
			' ريال',
		] );
	} );
} );
