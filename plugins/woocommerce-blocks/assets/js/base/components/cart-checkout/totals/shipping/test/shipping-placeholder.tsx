/**
 * External dependencies
 */
import { screen, render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import ShippingPlaceholder from '../shipping-placeholder';

const shippingCalculatorID = 'shipping-calculator-form-wrapper';

describe( 'ShippingPlaceholder', () => {
	it( 'should show correct text if showCalculator is false and addressProvided is false', () => {
		const { rerender } = render(
			<ShippingPlaceholder
				showCalculator={ false }
				addressProvided={ false }
				isCheckout={ true }
				isShippingCalculatorOpen={ false }
				setIsShippingCalculatorOpen={ jest.fn() }
				shippingCalculatorID={ shippingCalculatorID }
			/>
		);
		expect(
			screen.getByText( 'Enter address to calculate' )
		).toBeInTheDocument();
		rerender(
			<ShippingPlaceholder
				showCalculator={ false }
				isCheckout={ false }
				addressProvided={ false }
				isShippingCalculatorOpen={ false }
				setIsShippingCalculatorOpen={ jest.fn() }
				shippingCalculatorID={ shippingCalculatorID }
			/>
		);
		expect(
			screen.getByText( 'Calculated during checkout' )
		).toBeInTheDocument();
	} );

	it( 'should show correct text if showCalculator is false and addressProvided is true', () => {
		render(
			<ShippingPlaceholder
				showCalculator={ false }
				addressProvided={ true }
				isCheckout={ true }
				isShippingCalculatorOpen={ false }
				setIsShippingCalculatorOpen={ jest.fn() }
				shippingCalculatorID={ shippingCalculatorID }
			/>
		);
		expect(
			screen.getByText( 'No available delivery option' )
		).toBeInTheDocument();
	} );
} );
