import { beforeEach, describe, expect, it, vi, type Mock } from 'vitest';
const { mockSlotRender } = vi.hoisted( () => {
	const mockSlotRender = vi.fn( () => <div data-testid="discount-slot" /> );
	return {
		mockSlotRender,
	};
} );

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useStoreCart } from '@woocommerce/base-context/hooks';

/**
 * Internal dependencies
 */
import Block from '../block';
vi.mock( '@woocommerce/base-context/hooks', () => {
	const mock = {
		useStoreCart: vi.fn(),
		useStoreCartCoupons: vi.fn( () => ( {
			removeCoupon: vi.fn(),
			isRemovingCoupon: false,
		} ) ),
		useOrderSummaryLoadingState: vi.fn( () => ( {
			isLoading: false,
		} ) ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
vi.mock( '@woocommerce/blocks-checkout', () => {
	const MockFill = ( { children }: { children: React.ReactNode } ) => (
		<>{ children }</>
	);
	MockFill.Slot = ( props: Record< string, unknown > ) =>
		mockSlotRender( props );
	return ( ( mock ) => ( {
		default: mock,
		...mock,
	} ) )( {
		ExperimentalDiscountsMeta: MockFill,
		applyCheckoutFilter: vi.fn(
			( { defaultValue }: { defaultValue: unknown } ) => defaultValue
		),
	} );
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
	extensions: {
		some: 'data',
	},
	receiveCart: vi.fn(),
};
describe( 'Checkout Order Summary Discount Block', () => {
	beforeEach( () => {
		vi.clearAllMocks();
		( useStoreCart as Mock ).mockReturnValue( mockCartData );
	} );
	it( 'renders the DiscountsMeta slot with checkout context when no coupons', () => {
		render( <Block /> );
		expect( screen.getByTestId( 'discount-slot' ) ).toBeInTheDocument();
		expect( mockSlotRender ).toHaveBeenCalledWith(
			expect.objectContaining( {
				context: 'woocommerce/checkout',
				extensions: {
					some: 'data',
				},
			} )
		);
		const slotProps = mockSlotRender.mock.calls[ 0 ][ 0 ];
		expect( slotProps.cart ).not.toHaveProperty( 'receiveCart' );
	} );
	it( 'still renders the DiscountsMeta slot when coupons are present', () => {
		( useStoreCart as Mock ).mockReturnValue( {
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
