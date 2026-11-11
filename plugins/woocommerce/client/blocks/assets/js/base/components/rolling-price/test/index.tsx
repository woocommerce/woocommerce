/**
 * External dependencies
 */
import { act, render } from '@testing-library/react';
import { FormattedMonetaryAmount } from '@woocommerce/blocks-components';
import type { Currency } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { RollingPrice } from '../index';

const usd: Currency = {
	code: 'USD',
	symbol: '$',
	thousandSeparator: ',',
	decimalSeparator: '.',
	minorUnit: 2,
	prefix: '$',
	suffix: '',
};

const sar: Currency = {
	code: 'SAR',
	symbol: 'ر.س',
	thousandSeparator: '.',
	decimalSeparator: ',',
	minorUnit: 2,
	prefix: '',
	suffix: ' ر.س',
};

const eur: Currency = {
	code: 'EUR',
	symbol: '€',
	thousandSeparator: ' ',
	decimalSeparator: ',',
	minorUnit: 2,
	prefix: '€ ',
	suffix: '',
};

const columnSelector = '.wc-block-components-rolling-price__column';

/**
 * Gives every column a running transition, like a browser does while the digits roll. Like a browser animation, it
 * reports `finished` once `finish` is called.
 */
const mockRollingAnimations = () => {
	let resolveFinished = () => undefined as void;
	const animation = {
		playState: 'running',
		finished: new Promise< void >( ( resolve ) => {
			resolveFinished = resolve;
		} ),
		effect: { getComputedTiming: () => ( { endTime: 200 } ) },
	};
	Element.prototype.getAnimations = function ( this: Element ) {
		return this.matches( columnSelector )
			? [ animation as unknown as Animation ]
			: [];
	};
	return () => {
		animation.playState = 'finished';
		resolveFinished();
	};
};

describe( 'RollingPrice', () => {
	afterEach( () => {
		// jsdom has no Web Animations API, so remove the one that a test added.
		Reflect.deleteProperty( Element.prototype, 'getAnimations' );
	} );

	it.each( [
		[ 'a symbol before the price', usd, 123456 ],
		[ 'a multi-character symbol after the price', sar, 123456 ],
		[ 'a symbol with a space before the price', eur, 123456 ],
		[ 'a negative price', usd, -123456 ],
	] )(
		'renders the same markup as FormattedMonetaryAmount for %s',
		( _, currency, value ) => {
			const { container: rolling } = render(
				<RollingPrice currency={ currency } value={ value } />
			);
			const { container: plain } = render(
				<FormattedMonetaryAmount
					currency={ currency }
					value={ value }
				/>
			);

			expect( rolling.innerHTML ).toBe( plain.innerHTML );
		}
	);

	it( 'shows the new price as plain markup when nothing animates', () => {
		const { container, rerender } = render(
			<RollingPrice currency={ usd } value={ 1000 } />
		);

		rerender( <RollingPrice currency={ usd } value={ 123456 } /> );

		expect( container ).toHaveTextContent( '$1,234.56' );
		expect(
			container.querySelector( '.wc-block-components-rolling-price' )
		).toBeNull();
	} );

	it( 'rolls each digit from the old price, and keeps the plain price for assistive technology', async () => {
		const finish = mockRollingAnimations();
		const { container, rerender } = render(
			<RollingPrice currency={ sar } value={ 99999 } />
		);

		rerender( <RollingPrice currency={ sar } value={ 100000 } /> );

		const roll = container.querySelector(
			'.wc-block-components-rolling-price'
		);
		expect( roll ).toHaveAttribute( 'aria-hidden', 'true' );
		expect( roll ).toHaveAttribute( 'dir', 'ltr' );
		expect(
			container.querySelector( '.screen-reader-text' )
		).toHaveTextContent( '1.000,00' );
		// The currency symbol keeps its own markup outside the roll.
		expect(
			container.querySelector(
				'.wc-block-components-formatted-money-amount__currency-symbol'
			)
		).toHaveTextContent( 'ر.س' );

		const columns = Array.from(
			container.querySelectorAll< HTMLElement >( columnSelector )
		);
		expect(
			columns.map( ( column ) => [
				column.style.getPropertyValue( '--wc-roll-from' ),
				column.style.getPropertyValue( '--wc-roll-to' ),
			] )
		).toEqual( [
			[ '0', '1' ],
			[ '9', '0' ],
			[ '9', '0' ],
			[ '9', '0' ],
			[ '9', '0' ],
			[ '9', '0' ],
		] );

		await act( async () => finish() );

		expect(
			container.querySelector( '.wc-block-components-rolling-price' )
		).toBeNull();
		expect( container ).toHaveTextContent( '1.000,00 ر.س' );
	} );

	it( 'does not roll while it cannot roll, and rolls the first change after that', () => {
		mockRollingAnimations();
		const { container, rerender } = render(
			<RollingPrice currency={ usd } value={ 0 } canRoll={ false } />
		);
		const isRolling = () =>
			container.querySelector( '.wc-block-components-rolling-price' ) !==
			null;

		// The cart is still loading its first values.
		rerender(
			<RollingPrice currency={ usd } value={ 5000 } canRoll={ false } />
		);
		expect( isRolling() ).toBe( false );
		expect( container ).toHaveTextContent( '$50.00' );

		// The cart has loaded with the same value, so the next change is the shopper's and rolls.
		rerender( <RollingPrice currency={ usd } value={ 5000 } canRoll /> );
		rerender( <RollingPrice currency={ usd } value={ 6000 } canRoll /> );
		expect( isRolling() ).toBe( true );
	} );
} );
