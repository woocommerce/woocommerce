/**
 * External dependencies
 */
import { screen, render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ShippingPlaceholder from '../shipping-placeholder';

describe( 'ShippingPlaceholder', () => {
	it( 'should show correct text if showCalculator is false', () => {
		const { rerender } = render(
			<ShippingPlaceholder showCalculator={ false } isCheckout={ true } />
		);
		expect(
			screen.getByText( 'Enter the address to calculate' )
		).toBeInTheDocument();
		rerender(
			<ShippingPlaceholder
				showCalculator={ false }
				isCheckout={ false }
			/>
		);
		expect(
			screen.getByText( 'Calculated during checkout' )
		).toBeInTheDocument();
	} );
} );
