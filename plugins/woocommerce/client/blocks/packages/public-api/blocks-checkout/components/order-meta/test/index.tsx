/**
 * External dependencies
 */
import { screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ExperimentalOrderMeta from '..';
import { renderSlotFill, getFillProps } from '../../../slot/test-utils';

const defaultSlotProps = {
	extensions: {},
	cart: {},
	context: 'woocommerce/checkout',
};

describe( 'ExperimentalOrderMeta', () => {
	it( 'renders fill content inside the slot', () => {
		renderSlotFill( ExperimentalOrderMeta, defaultSlotProps );

		expect( screen.getByTestId( 'fill-content' ) ).toBeInTheDocument();
	} );

	it( 'wraps the slot in a TotalsWrapper with expected classes', () => {
		const { container } = renderSlotFill( ExperimentalOrderMeta, {
			...defaultSlotProps,
			className: 'custom-class',
		} );

		const wrapper = container.querySelector(
			'.wc-block-components-totals-wrapper.slot-wrapper'
		);
		expect( wrapper ).toBeInTheDocument();

		const slot = container.querySelector(
			'.wc-block-components-order-meta'
		);
		expect( slot ).toBeInTheDocument();
		expect( slot ).toHaveClass( 'custom-class' );
	} );

	it( 'passes extensions, cart, and context via fillProps', () => {
		const extensions = { 'my-extension': { key: 'value' } };
		const cart = { items: [], totals: {} };
		const context = 'woocommerce/cart';

		const fillProps = getFillProps( ExperimentalOrderMeta, {
			extensions,
			cart,
			context,
		} );

		expect( fillProps ).toEqual(
			expect.objectContaining( { extensions, cart, context } )
		);
	} );
} );
