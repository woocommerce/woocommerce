/**
 * External dependencies
 */
import { screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ExperimentalDiscountsMeta from '..';
import { renderSlotFill, getFillProps } from '../../../slot/test-utils';

const defaultSlotProps = {
	extensions: {},
	cart: {},
	context: 'woocommerce/checkout',
};

describe( 'ExperimentalDiscountsMeta', () => {
	it( 'renders fill content inside the slot', () => {
		renderSlotFill( ExperimentalDiscountsMeta, defaultSlotProps );

		expect( screen.getByTestId( 'fill-content' ) ).toBeInTheDocument();
	} );

	it( 'wraps the slot in a TotalsWrapper with expected classes', () => {
		const { container } = renderSlotFill( ExperimentalDiscountsMeta, {
			...defaultSlotProps,
			className: 'my-discount-class',
		} );

		const wrapper = container.querySelector(
			'.wc-block-components-totals-wrapper.slot-wrapper'
		);
		expect( wrapper ).toBeInTheDocument();

		const slot = container.querySelector(
			'.wc-block-components-discounts-meta'
		);
		expect( slot ).toBeInTheDocument();
		expect( slot ).toHaveClass( 'my-discount-class' );
	} );

	it( 'passes extensions, cart, and context via fillProps', () => {
		const extensions = { 'discount-ext': { active: true } };
		const cart = { coupons: [ { code: 'SAVE10' } ] };
		const context = 'woocommerce/cart';

		const fillProps = getFillProps( ExperimentalDiscountsMeta, {
			extensions,
			cart,
			context,
		} );

		expect( fillProps ).toEqual(
			expect.objectContaining( { extensions, cart, context } )
		);
	} );
} );
