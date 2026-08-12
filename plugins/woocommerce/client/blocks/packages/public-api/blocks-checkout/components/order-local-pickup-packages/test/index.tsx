/**
 * External dependencies
 */
import { screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ExperimentalOrderLocalPickupPackages from '..';
import { renderSlotFill, getFillProps } from '../../../slot/test-utils';

describe( 'ExperimentalOrderLocalPickupPackages', () => {
	const defaultSlotProps = {
		extensions: {},
		cart: {},
		components: {},
		renderPickupLocation: jest.fn(),
	};

	it( 'renders fill content inside the slot with expected classes', () => {
		const { container } = renderSlotFill(
			ExperimentalOrderLocalPickupPackages,
			defaultSlotProps
		);

		expect( screen.getByTestId( 'fill-content' ) ).toBeInTheDocument();
		expect(
			container.querySelector(
				'.wc-block-components-local-pickup-rates-control'
			)
		).toBeInTheDocument();
	} );

	it( 'passes all expected fillProps', () => {
		const extensions = { 'pickup-ext': { locations: [] } };
		const cart = { items: [ { id: 1 } ] };
		const components = { PickupOption: () => null };
		const renderPickupLocation = jest.fn();

		const fillProps = getFillProps( ExperimentalOrderLocalPickupPackages, {
			extensions,
			cart,
			components,
			renderPickupLocation,
		} );

		expect( fillProps ).toEqual(
			expect.objectContaining( {
				extensions,
				cart,
				components,
				renderPickupLocation,
			} )
		);
	} );
} );
