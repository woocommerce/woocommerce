import { beforeEach, describe, expect, it, vi } from 'vitest';
const { mockUseCheckoutSubmit } = vi.hoisted( () => {
	const mockUseCheckoutSubmit = vi.fn();
	return {
		mockUseCheckoutSubmit,
	};
} );

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import PlaceOrderButton from '..';
vi.mock( '@woocommerce/base-context/hooks', () => {
	const mock = {
		useCheckoutSubmit: () => mockUseCheckoutSubmit(),
		usePaymentMethodInterface: () => ( {
			onSubmit: vi.fn(),
			validate: vi.fn(),
			activePaymentMethod: 'test-payment',
		} ),
		useStoreCart: () => ( {
			cartIsLoading: false,
		} ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/blocks-components', () => {
	const mock = {
		FormattedMonetaryAmount: () => <span>$10.00</span>,
		Spinner: () => <span>Loading...</span>,
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
const CustomButtonMock = vi.fn( () => <button>Custom Button</button> );
describe( 'PlaceOrderButton', () => {
	beforeEach( () => {
		vi.clearAllMocks();
		mockUseCheckoutSubmit.mockReturnValue( {
			onSubmit: vi.fn(),
			isCalculating: false,
			isDisabled: false,
			waitingForProcessing: false,
			waitingForRedirect: false,
		} );
	} );
	it( 'renders default button', () => {
		render( <PlaceOrderButton label="Place Order" /> );
		expect( screen.queryByText( 'Place Order' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Custom Button' ) ).not.toBeInTheDocument();
	} );
	it( 'displays the provided label', () => {
		render( <PlaceOrderButton label="Confirm Purchase" /> );
		expect( screen.getByText( 'Confirm Purchase' ) ).toBeInTheDocument();
	} );
	it( 'renders CustomButtonComponent when provided', () => {
		render(
			<PlaceOrderButton
				label="Place Order"
				CustomButtonComponent={ CustomButtonMock }
			/>
		);
		expect( screen.queryByText( 'Custom Button' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Place Order' ) ).not.toBeInTheDocument();
	} );
	it( 'spreads paymentMethodInterface props to the custom component', () => {
		render(
			<PlaceOrderButton
				label="Place Order"
				CustomButtonComponent={ CustomButtonMock }
			/>
		);
		expect( CustomButtonMock ).toHaveBeenCalledWith(
			expect.objectContaining( {
				onSubmit: expect.any( Function ),
				validate: expect.any( Function ),
				activePaymentMethod: 'test-payment',
			} ),
			expect.anything()
		);
	} );
} );
