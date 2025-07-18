/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { SlotFillProvider } from '@wordpress/components';
import {
	useStoreCart,
	useStoreCartCoupons,
} from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import Block from '../block';

// We only need to mock the hooks since we're using real components
jest.mock( '@woocommerce/base-context/hooks', () => ( {
	useStoreCart: jest.fn(),
	useStoreCartCoupons: jest.fn(),
	useOrderSummaryLoadingState: jest.fn( () => {
		return {
			isLoading: false,
		};
	} ),
} ) );

// Mock the ExperimentalDiscountsMeta to track when slot is rendered
const mockSlotRender = jest.fn();
jest.mock( '@woocommerce/blocks-checkout', () => {
	const actual = jest.requireActual( '@woocommerce/blocks-checkout' );
	return {
		...actual,
		ExperimentalDiscountsMeta: {
			...actual.ExperimentalDiscountsMeta,
			Slot: ( props: unknown ) => {
				mockSlotRender( props );
				// Return a testable element that represents the slot
				return <div data-testid="discount-slot" />;
			},
		},
	};
} );

const mockCartTotals = {
	currency_code: 'USD',
	currency_symbol: '$',
	currency_minor_unit: 2,
	currency_decimal_separator: '.',
	currency_thousand_separator: ',',
	currency_prefix: '$',
	currency_suffix: '',
	total_discount: '0',
	total_discount_tax: '0',
};

const mockCartData = {
	cartTotals: mockCartTotals,
	cartCoupons: [],
	extensions: { some: 'data' },
	receiveCart: jest.fn(),
	otherCartData: { test: 'value' },
};

const mockCouponHooks = {
	removeCoupon: jest.fn(),
	isRemovingCoupon: false,
};

describe( 'Cart Order Summary Discount Block', () => {
	beforeEach( () => {
		jest.clearAllMocks();
		( useStoreCart as jest.Mock ).mockReturnValue( mockCartData );
		( useStoreCartCoupons as jest.Mock ).mockReturnValue( mockCouponHooks );
	} );

	const renderWithProviders = ( ui: React.ReactElement ) => {
		return render( <SlotFillProvider>{ ui }</SlotFillProvider> );
	};

	it( 'renders only the DiscountSlotFill when there are no coupons', () => {
		renderWithProviders( <Block className="test-class" /> );

		// Verify the slot is rendered
		expect( screen.getByTestId( 'discount-slot' ) ).toBeInTheDocument();

		// Since we're using real TotalsWrapper, it won't render when there are no children
		// TotalsDiscount should not be present when there are no coupons
		expect( screen.queryByText( /discount/i ) ).not.toBeInTheDocument();

		// Verify the slot was called with correct props
		expect( mockSlotRender ).toHaveBeenCalledWith(
			expect.objectContaining( {
				context: 'woocommerce/cart',
				extensions: { some: 'data' },
				cart: expect.objectContaining( {
					cartTotals: mockCartTotals,
					otherCartData: { test: 'value' },
				} ),
			} )
		);

		// Verify receiveCart was not passed to the slot
		const slotProps = mockSlotRender.mock.calls[ 0 ][ 0 ];
		expect( slotProps.cart ).not.toHaveProperty( 'receiveCart' );
	} );

	it( 'renders both TotalsDiscount and DiscountSlotFill when there are coupons', () => {
		const mockCoupons = [
			{
				code: 'TEST10',
				label: 'TEST10', // The label is what gets displayed
				discount_type: 'percent',
				amount: '10',
				totals: {
					total_discount: '1000',
					total_discount_tax: '0',
				},
			},
		];

		( useStoreCart as jest.Mock ).mockReturnValue( {
			...mockCartData,
			cartCoupons: mockCoupons,
			cartTotals: {
				...mockCartTotals,
				total_discount: '1000',
			},
		} );

		renderWithProviders( <Block className="test-class" /> );

		// With real components, look for the discount text/coupon code
		expect( screen.getByText( 'TEST10' ) ).toBeInTheDocument();

		// Verify the slot is still rendered
		expect( screen.getByTestId( 'discount-slot' ) ).toBeInTheDocument();

		// The wrapper should have the provided class
		const wrapper = screen.getByText( 'TEST10' ).closest( '.test-class' );
		expect( wrapper ).toBeInTheDocument();
	} );

	it( 'calls useStoreCartCoupons with correct context', () => {
		renderWithProviders( <Block className="test-class" /> );

		expect( useStoreCartCoupons ).toHaveBeenCalledWith( 'wc/cart' );
	} );

	it( 'always renders the ExperimentalDiscountsMeta.Slot regardless of coupon state', () => {
		// Test with no coupons
		const { rerender } = renderWithProviders(
			<Block className="test-class" />
		);
		expect( screen.getByTestId( 'discount-slot' ) ).toBeInTheDocument();
		expect( mockSlotRender ).toHaveBeenCalledTimes( 1 );

		// Clean up
		jest.clearAllMocks();

		// Test with coupons
		( useStoreCart as jest.Mock ).mockReturnValue( {
			...mockCartData,
			cartCoupons: [
				{
					code: 'TEST',
					label: 'TEST', // Add label for display
					totals: {
						total_discount: '500',
						total_discount_tax: '0',
					},
				},
			],
		} );

		rerender(
			<SlotFillProvider>
				<Block className="test-class" />
			</SlotFillProvider>
		);

		// Should still have the slot
		expect( screen.getByTestId( 'discount-slot' ) ).toBeInTheDocument();

		// Verify it was called with correct props both times
		expect( mockSlotRender ).toHaveBeenCalledTimes( 1 );

		// Check the props structure
		expect( mockSlotRender ).toHaveBeenCalledWith(
			expect.objectContaining( {
				context: 'woocommerce/cart',
				extensions: expect.any( Object ),
				cart: expect.objectContaining( {
					cartTotals: expect.any( Object ),
				} ),
			} )
		);

		// Verify receiveCart is not passed
		const slotProps = mockSlotRender.mock.calls[ 0 ][ 0 ];
		expect( slotProps.cart ).not.toHaveProperty( 'receiveCart' );
	} );

	it( 'handles missing className prop gracefully', () => {
		( useStoreCart as jest.Mock ).mockReturnValue( {
			...mockCartData,
			cartCoupons: [
				{
					code: 'TEST',
					label: 'TEST', // Add label for display
					totals: {
						total_discount: '500',
						total_discount_tax: '0',
					},
				},
			],
		} );

		// @ts-expect-error Testing without required prop
		renderWithProviders( <Block /> );

		// Should still render without errors
		expect( screen.getByText( 'TEST' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'discount-slot' ) ).toBeInTheDocument();
	} );
} );
