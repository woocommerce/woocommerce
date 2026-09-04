/**
 * External dependencies
 */
import { screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ExperimentalOrderShippingPackages from '..';
import { renderSlotFill, getFillProps } from '../../../slot/test-utils';

describe( 'ExperimentalOrderShippingPackages', () => {
	const defaultSlotProps = {
		extensions: {},
		cart: {},
		components: {},
		context: 'woocommerce/checkout',
		noResultsMessage: 'No shipping options.',
		renderOption: jest.fn(),
		collapsible: false,
		showItems: false,
	};

	it( 'renders fill content inside the slot with expected classes', () => {
		const { container } = renderSlotFill(
			ExperimentalOrderShippingPackages,
			{ ...defaultSlotProps, className: 'custom-shipping' }
		);

		expect( screen.getByTestId( 'fill-content' ) ).toBeInTheDocument();

		const slot = container.querySelector(
			'.wc-block-components-shipping-rates-control'
		);
		expect( slot ).toBeInTheDocument();
		expect( slot ).toHaveClass( 'custom-shipping' );
	} );

	it( 'passes all expected fillProps', () => {
		const extensions = { 'shipping-ext': { zones: [] } };
		const cart = { shippingRates: [ { rate: '5.00' } ] };
		const components = { ShippingRate: () => null };
		const renderOption = jest.fn();

		const fillProps = getFillProps( ExperimentalOrderShippingPackages, {
			extensions,
			cart,
			components,
			context: 'woocommerce/cart',
			noResultsMessage: 'No options available',
			renderOption,
			collapsible: true,
			showItems: true,
		} );

		expect( fillProps ).toEqual(
			expect.objectContaining( {
				extensions,
				cart,
				components,
				context: 'woocommerce/cart',
				noResultsMessage: 'No options available',
				renderOption,
				collapsible: true,
				collapse: true,
				showItems: true,
			} )
		);
	} );
} );
