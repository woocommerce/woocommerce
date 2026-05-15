/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SummaryNumber from '../number';

describe( 'SummaryNumber', () => {
	test( 'applies the is-bad-trend class for a negative delta so the badge gets the high-contrast accessible color (RSMAPGJ-291 / woo#37987)', () => {
		const { container } = render(
			<SummaryNumber
				label="Total sales"
				value="$100"
				prevValue="$200"
				delta={ -50 }
			/>
		);

		const item = container.querySelector( '.woocommerce-summary__item' );
		expect( item ).not.toBeNull();
		expect( item.classList.contains( 'is-bad-trend' ) ).toBe( true );
		expect( item.classList.contains( 'is-good-trend' ) ).toBe( false );

		// The delta badge must be rendered — the CSS rule that gives it the
		// accessible white text depends on this element + the is-bad-trend
		// class on its ancestor.
		const delta = container.querySelector(
			'.woocommerce-summary__item-delta'
		);
		expect( delta ).not.toBeNull();
	} );

	test( 'applies the is-good-trend class for a positive delta', () => {
		const { container } = render(
			<SummaryNumber
				label="Total sales"
				value="$300"
				prevValue="$200"
				delta={ 50 }
			/>
		);

		const item = container.querySelector( '.woocommerce-summary__item' );
		expect( item.classList.contains( 'is-good-trend' ) ).toBe( true );
		expect( item.classList.contains( 'is-bad-trend' ) ).toBe( false );
	} );

	test( 'inverts good/bad trend classes when reverseTrend is set', () => {
		const { container } = render(
			<SummaryNumber
				label="Refunds"
				value="$50"
				prevValue="$10"
				delta={ 50 }
				reverseTrend
			/>
		);

		const item = container.querySelector( '.woocommerce-summary__item' );
		expect( item.classList.contains( 'is-bad-trend' ) ).toBe( true );
		expect( item.classList.contains( 'is-good-trend' ) ).toBe( false );
	} );
} );
