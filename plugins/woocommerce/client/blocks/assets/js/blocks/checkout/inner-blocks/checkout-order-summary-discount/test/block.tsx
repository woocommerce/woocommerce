/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useStoreCart } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import Block from '../block';

jest.mock( '@woocommerce/base-context/hooks', () => ( {
	useStoreCart: jest.fn(),
	useStoreCartCoupons: jest.fn( () => ( {
		removeCoupon: jest.fn(),
		isRemovingCoupon: false,
	} ) ),
	useOrderSummaryLoadingState: jest.fn( () => ( { isLoading: false } ) ),
} ) );

const mockSlotRender = jest.fn( () => <div data-testid="discount-slot" /> );
jest.mock( '@woocommerce/blocks-checkout', () => {
	const MockFill = ( { children }: { children: React.ReactNode } ) => (
		<>{ children }</>
	);
	MockFill.Slot = ( props: Record< string, unknown > ) =>
		mockSlotRender( props );
	return {
		ExperimentalDiscountsMeta: MockFill,
		applyCheckoutFilter: jest.fn(
			( { defaultValue }: { defaultValue: unknown } ) => defaultValue
		),
	};
} );

const mockCartData = {
	cartTotals: {
		currency_code: 'USD',
		currency_symbol: '$',
		currency_minor_unit: 2,
		currency_decimal_separator: '.',
		currency_thousand_separator: ',',
		currency_prefix: '$',
		currency_suffix: '',
		total_discount: '0',
		total_discount_tax: '0',
	},
	cartCoupons: [],
	extensions: { some: 'data' },
	receiveCart: jest.fn(),
};

describe( 'Checkout Order Summary Discount Block', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		( useStoreCart as jest.Mock ).mockReturnValue( mockCartData );
	} );

	it( 'renders the DiscountsMeta slot with checkout context when no coupons', () => {
		render( <Block /> );

		expect( screen.getByTestId( 'discount-slot' ) ).toBeInTheDocument();
		expect( mockSlotRender ).toHaveBeenCalledWith(
			expect.objectContaining( {
				context: 'woocommerce/checkout',
				extensions: { some: 'data' },
			} )
		);

		const slotProps = mockSlotRender.mock.calls[ 0 ][ 0 ];
		expect( slotProps.cart ).not.toHaveProperty( 'receiveCart' );
	} );

	it( 'still renders the DiscountsMeta slot when coupons are present', () => {
		( useStoreCart as jest.Mock ).mockReturnValue( {
			...mockCartData,
			cartCoupons: [
				{
					code: 'SAVE10',
					label: 'SAVE10',
					totals: {
						total_discount: '1000',
						total_discount_tax: '0',
					},
				},
			],
			cartTotals: {
				...mockCartData.cartTotals,
				total_discount: '1000',
			},
		} );

		render( <Block className="test-class" /> );

		expect( screen.getByTestId( 'discount-slot' ) ).toBeInTheDocument();
	} );
} );
