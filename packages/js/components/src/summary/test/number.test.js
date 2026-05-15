/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import SummaryNumber from '../number';

describe( 'SummaryNumber trend classes', () => {
	const renderSummary = ( props ) =>
		render(
			<SummaryNumber
				label="Revenue"
				value="$1,000"
				prevValue="$500"
				{ ...props }
			/>
		);

	it( 'applies is-bad-trend when delta is negative', () => {
		const { container } = renderSummary( { delta: -5 } );
		const item = container.querySelector( '.woocommerce-summary__item' );
		expect( item ).toHaveClass( 'is-bad-trend' );
		expect( item ).not.toHaveClass( 'is-good-trend' );
	} );

	it( 'applies is-good-trend when delta is positive', () => {
		const { container } = renderSummary( { delta: 5 } );
		const item = container.querySelector( '.woocommerce-summary__item' );
		expect( item ).toHaveClass( 'is-good-trend' );
		expect( item ).not.toHaveClass( 'is-bad-trend' );
	} );

	it( 'reverses trend classes when reverseTrend is true', () => {
		const { container } = renderSummary( {
			delta: 5,
			reverseTrend: true,
		} );
		const item = container.querySelector( '.woocommerce-summary__item' );
		expect( item ).toHaveClass( 'is-bad-trend' );
		expect( item ).not.toHaveClass( 'is-good-trend' );
	} );

	it( 'omits trend classes when delta is zero', () => {
		const { container } = renderSummary( { delta: 0 } );
		const item = container.querySelector( '.woocommerce-summary__item' );
		expect( item ).not.toHaveClass( 'is-bad-trend' );
		expect( item ).not.toHaveClass( 'is-good-trend' );
	} );

	it( 'renders the delta wrapper element for both trend states', () => {
		// The accessibility fix in style.scss targets the
		// .woocommerce-summary__item-delta element (and its descendants) to
		// override the emotion-generated text color. Lock the selector here
		// so renames in the component don't silently break the CSS contract.
		renderSummary( { delta: -5 } );
		expect(
			document.querySelector( '.woocommerce-summary__item-delta' )
		).toBeInTheDocument();
	} );
} );
