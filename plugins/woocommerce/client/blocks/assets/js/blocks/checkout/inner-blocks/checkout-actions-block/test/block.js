/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Block from '../block';

const mockPlaceOrderButton = jest.fn( ( { label, CustomButtonComponent } ) => {
	if ( CustomButtonComponent ) {
		return <CustomButtonComponent />;
	}
	return <button>{ label }</button>;
} );

jest.mock( '@woocommerce/base-components/cart-checkout', () => ( {
	PlaceOrderButton: ( props ) => mockPlaceOrderButton( props ),
	ReturnToCartButton: ( { children, href } ) => (
		<a href={ href }>{ children }</a>
	),
} ) );

const mockUseCheckoutSubmit = jest.fn();
jest.mock( '@woocommerce/base-context/hooks', () => ( {
	useCheckoutSubmit: () => mockUseCheckoutSubmit(),
} ) );

const mockUseSelect = jest.fn();
jest.mock( '@wordpress/data', () => ( {
	useSelect: () => mockUseSelect(),
} ) );

jest.mock( '@woocommerce/block-data', () => ( {
	paymentStore: 'wc/store/payment',
} ) );

let mockCheckoutShowTerms = true;
jest.mock( '@woocommerce/settings', () => ( {
	getSetting: jest.fn( ( key, fallback ) =>
		key === 'checkoutShowTerms' ? mockCheckoutShowTerms : fallback
	),
} ) );

jest.mock( '@woocommerce/base-context', () => ( {
	noticeContexts: {
		CHECKOUT_ACTIONS: 'wc/checkout/checkout-actions',
	},
} ) );

jest.mock( '@woocommerce/blocks-components', () => ( {
	StoreNoticesContainer: () => null,
} ) );

jest.mock( '@woocommerce/blocks-checkout', () => ( {
	applyCheckoutFilter: ( { defaultValue } ) => defaultValue,
} ) );

jest.mock( '@woocommerce/block-settings', () => ( {
	CART_URL: '/cart',
} ) );

jest.mock( '../../checkout-order-summary-block/slotfills', () => ( {
	CheckoutOrderSummarySlot: () => null,
} ) );

jest.mock(
	'../../checkout-terms-block/frontend',
	() =>
		( { checkbox, text, className } ) => (
			<div
				data-testid="terms"
				data-checkbox={ checkbox ? 'yes' : 'no' }
				className={ className }
			>
				{ text }
			</div>
		)
);

jest.mock( '../../checkout-actions-block/constants', () => ( {
	defaultPlaceOrderButtonLabel: 'Place Order',
} ) );

const defaultProps = {
	cartPageId: 1,
	showReturnToCart: false,
	placeOrderButtonLabel: 'Place Order',
	priceSeparator: '·',
	returnToCartButtonLabel: 'Return to Cart',
};

const CustomPlaceOrderButton = () => <button>Custom Button</button>;

describe( 'Checkout Actions Block', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		mockPlaceOrderButton.mockClear();
		mockCheckoutShowTerms = true;
		mockUseCheckoutSubmit.mockReturnValue( {
			paymentMethodButtonLabel: '',
			paymentMethodPlaceOrderButton: undefined,
		} );
		mockUseSelect.mockReturnValue( null );
	} );

	it( 'does not pass CustomButtonComponent to PlaceOrderButton when a saved token is active', () => {
		mockUseCheckoutSubmit.mockReturnValue( {
			paymentMethodButtonLabel: '',
			paymentMethodPlaceOrderButton: CustomPlaceOrderButton,
		} );
		mockUseSelect.mockReturnValue( 'saved-token-123' );

		render( <Block { ...defaultProps } /> );

		expect( mockPlaceOrderButton ).toHaveBeenCalledWith(
			expect.objectContaining( {
				CustomButtonComponent: undefined,
			} )
		);
		expect( screen.queryByText( 'Place Order' ) ).toBeInTheDocument();
		expect( screen.queryByText( 'Custom Button' ) ).not.toBeInTheDocument();
	} );

	it( 'passes CustomButtonComponent to PlaceOrderButton when no saved token is active', () => {
		mockUseCheckoutSubmit.mockReturnValue( {
			paymentMethodButtonLabel: '',
			paymentMethodPlaceOrderButton: CustomPlaceOrderButton,
		} );
		mockUseSelect.mockReturnValue( null );

		render( <Block { ...defaultProps } /> );

		expect( mockPlaceOrderButton ).toHaveBeenCalledWith(
			expect.objectContaining( {
				CustomButtonComponent: CustomPlaceOrderButton,
			} )
		);
		expect( screen.queryByText( 'Place Order' ) ).not.toBeInTheDocument();
		expect( screen.queryByText( 'Custom Button' ) ).toBeInTheDocument();
	} );

	it( 'passes undefined CustomButtonComponent when payment method does not provide one', () => {
		mockUseCheckoutSubmit.mockReturnValue( {
			paymentMethodButtonLabel: '',
			paymentMethodPlaceOrderButton: undefined,
		} );
		mockUseSelect.mockReturnValue( null );

		render( <Block { ...defaultProps } /> );

		expect( mockPlaceOrderButton ).toHaveBeenCalledWith(
			expect.objectContaining( {
				CustomButtonComponent: undefined,
			} )
		);
		expect( screen.queryByText( 'Place Order' ) ).toBeInTheDocument();
	} );

	it( 'uses payment method button label when provided', () => {
		mockUseCheckoutSubmit.mockReturnValue( {
			paymentMethodButtonLabel: 'Pay with Card',
			paymentMethodPlaceOrderButton: undefined,
		} );
		mockUseSelect.mockReturnValue( null );

		render( <Block { ...defaultProps } /> );

		expect( mockPlaceOrderButton ).toHaveBeenCalledWith(
			expect.objectContaining( {
				label: 'Pay with Card',
			} )
		);
		expect( screen.queryByText( 'Pay with Card' ) ).toBeInTheDocument();
	} );

	describe( 'terms and conditions', () => {
		const isBefore = ( a, b ) => {
			const elements = Array.from(
				document.body.querySelectorAll( '*' )
			);
			return elements.indexOf( a ) < elements.indexOf( b );
		};

		it( 'renders checkbox terms above the Place order button', () => {
			render(
				<Block
					{ ...defaultProps }
					showTerms={ true }
					termsCheckbox={ true }
					termsText="Custom"
				/>
			);

			const terms = screen.getByTestId( 'terms' );
			expect( terms ).toHaveAttribute( 'data-checkbox', 'yes' );
			expect( terms ).toHaveTextContent( 'Custom' );
			expect( isBefore( terms, screen.getByRole( 'button' ) ) ).toBe(
				true
			);
		} );

		it( 'keeps the Terms block wrapper class that extensions target', () => {
			render( <Block { ...defaultProps } showTerms={ true } /> );

			expect( screen.getByTestId( 'terms' ) ).toHaveClass(
				'wp-block-woocommerce-checkout-terms-block'
			);
		} );

		it( 'renders text-only terms below the Place order button', () => {
			render(
				<Block
					{ ...defaultProps }
					showTerms={ true }
					termsCheckbox={ false }
				/>
			);

			const terms = screen.getByTestId( 'terms' );
			expect( terms ).toHaveAttribute( 'data-checkbox', 'no' );
			expect( isBefore( screen.getByRole( 'button' ), terms ) ).toBe(
				true
			);
		} );

		it( 'renders no terms when showTerms is false', () => {
			render( <Block { ...defaultProps } showTerms={ false } /> );

			expect( screen.queryByTestId( 'terms' ) ).not.toBeInTheDocument();
		} );

		it( 'renders no terms when an extension hides them with woocommerce_checkout_show_terms', () => {
			mockCheckoutShowTerms = false;
			render(
				<Block
					{ ...defaultProps }
					showTerms={ true }
					termsCheckbox={ true }
				/>
			);

			expect( screen.queryByTestId( 'terms' ) ).not.toBeInTheDocument();
		} );

		it( 'renders no terms when showTerms is not set', () => {
			render( <Block { ...defaultProps } /> );

			expect( screen.queryByTestId( 'terms' ) ).not.toBeInTheDocument();
		} );
	} );

	describe( 'Return to Cart', () => {
		const getRow = () =>
			document.querySelector( '.wc-block-checkout__actions_row' );

		it( 'renders the Return to Cart link when enabled', () => {
			render( <Block { ...defaultProps } showReturnToCart={ true } /> );

			expect(
				screen.getByRole( 'link', { name: 'Return to Cart' } )
			).toHaveAttribute( 'href', '/cart' );
			expect( getRow() ).toHaveClass(
				'wc-block-checkout__actions_row--with-return-to-cart'
			);
		} );

		it( 'does not render the Return to Cart link when disabled', () => {
			render( <Block { ...defaultProps } showReturnToCart={ false } /> );

			expect(
				screen.queryByRole( 'link', { name: 'Return to Cart' } )
			).not.toBeInTheDocument();
			expect( getRow() ).not.toHaveClass(
				'wc-block-checkout__actions_row--with-return-to-cart'
			);
		} );

		it( 'keeps the Place order button full width within its column', () => {
			render( <Block { ...defaultProps } showReturnToCart={ true } /> );

			expect( mockPlaceOrderButton ).toHaveBeenCalledWith(
				expect.objectContaining( { fullWidth: true } )
			);
		} );

		it( 'puts text-only terms in the same column as the Place order button', () => {
			render(
				<Block
					{ ...defaultProps }
					showReturnToCart={ true }
					showTerms={ true }
					termsCheckbox={ false }
				/>
			);

			expect( screen.getByTestId( 'terms' ).parentElement ).toBe(
				getRow()
			);
		} );

		it( 'keeps checkbox terms above the row', () => {
			render(
				<Block
					{ ...defaultProps }
					showReturnToCart={ true }
					showTerms={ true }
					termsCheckbox={ true }
				/>
			);

			expect( getRow() ).not.toContainElement(
				screen.getByTestId( 'terms' )
			);
		} );
	} );
} );
