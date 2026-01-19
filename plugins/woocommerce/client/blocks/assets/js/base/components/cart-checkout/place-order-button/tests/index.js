/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import PlaceOrderButton from '..';

const mockUseCheckoutSubmit = jest.fn();
jest.mock( '@woocommerce/base-context/hooks', () => ( {
	useCheckoutSubmit: () => mockUseCheckoutSubmit(),
	usePaymentMethodInterface: () => ( {
		onSubmit: jest.fn(),
		validate: jest.fn(),
		activePaymentMethod: 'test-payment',
	} ),
	useStoreCart: () => ( {
		cartIsLoading: false,
	} ),
} ) );

jest.mock( '@woocommerce/blocks-components', () => ( {
	FormattedMonetaryAmount: () => <span>$10.00</span>,
	Spinner: () => <span>Loading...</span>,
} ) );

const CustomButtonMock = jest.fn( () => <button>Custom Button</button> );

describe( 'PlaceOrderButton', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockUseCheckoutSubmit.mockReturnValue( {
			onSubmit: jest.fn(),
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
