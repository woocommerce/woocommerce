// Mock all dependencies first, before any imports
jest.mock( '../cart/use-store-cart', () => ( {
	useStoreCart: jest.fn(),
} ) );

jest.mock( '../cart/use-store-cart-coupons', () => ( {
	useStoreCartCoupons: jest.fn(),
} ) );

jest.mock( '@wordpress/data', () => ( {
	...jest.requireActual( '@wordpress/data' ),
	useSelect: jest.fn(),
} ) );

jest.mock( '@woocommerce/block-data', () => ( {
	checkoutStore: {
		name: 'wc/store/checkout',
	},
} ) );

/**
 * External dependencies
 */
import { renderHook } from '@testing-library/react';
import { useSelect } from '@wordpress/data';

/**
 * Internal dependencies
 */
import {
	useOrderSummaryLoadingState,
	useOrderSummaryRow,
} from '../use-order-summary-loading-state';
import { useStoreCart } from '../cart/use-store-cart';
import { useStoreCartCoupons } from '../cart/use-store-cart-coupons';

describe( 'useOrderSummaryLoadingState', () => {
	beforeEach( () => {
		// Reset and set up default mocks
		useStoreCart.mockReturnValue( {
			cartIsLoading: false,
			isLoadingRates: false,
			hasPendingItemsOperations: false,
		} );

		useStoreCartCoupons.mockReturnValue( {
			isApplyingCoupon: false,
			isRemovingCoupon: false,
		} );

		useSelect.mockReturnValue( false );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'should return isLoading: false when no loading states are active', () => {
		const { result } = renderHook( () => useOrderSummaryLoadingState() );
		expect( result.current.isLoading ).toBe( false );
	} );

	it( 'should return isLoading: true when cartIsLoading is true', () => {
		useStoreCart.mockReturnValue( {
			cartIsLoading: true,
			isLoadingRates: false,
			hasPendingItemsOperations: false,
		} );

		const { result } = renderHook( () => useOrderSummaryLoadingState() );
		expect( result.current.isLoading ).toBe( true );
	} );

	it( 'should return isLoading: true when isLoadingRates is true', () => {
		useStoreCart.mockReturnValue( {
			cartIsLoading: false,
			isLoadingRates: true,
			hasPendingItemsOperations: false,
		} );

		const { result } = renderHook( () => useOrderSummaryLoadingState() );
		expect( result.current.isLoading ).toBe( true );
	} );

	it( 'should return isLoading: true when hasPendingItemsOperations is true', () => {
		useStoreCart.mockReturnValue( {
			cartIsLoading: false,
			isLoadingRates: false,
			hasPendingItemsOperations: true,
		} );

		const { result } = renderHook( () => useOrderSummaryLoadingState() );
		expect( result.current.isLoading ).toBe( true );
	} );

	it( 'should return isLoading: true when isApplyingCoupon is true', () => {
		useStoreCartCoupons.mockReturnValue( {
			isApplyingCoupon: true,
			isRemovingCoupon: false,
		} );

		const { result } = renderHook( () => useOrderSummaryLoadingState() );
		expect( result.current.isLoading ).toBe( true );
	} );

	it( 'should return isLoading: true when isRemovingCoupon is true', () => {
		useStoreCartCoupons.mockReturnValue( {
			isApplyingCoupon: false,
			isRemovingCoupon: true,
		} );

		const { result } = renderHook( () => useOrderSummaryLoadingState() );
		expect( result.current.isLoading ).toBe( true );
	} );

	it( 'should return isLoading: true when isCalculating is true', () => {
		useSelect.mockReturnValue( true );

		const { result } = renderHook( () => useOrderSummaryLoadingState() );
		expect( result.current.isLoading ).toBe( true );
	} );

	it( 'should return isLoading: true when multiple loading states are active', () => {
		useStoreCart.mockReturnValue( {
			cartIsLoading: true,
			isLoadingRates: true,
			hasPendingItemsOperations: false,
		} );

		useStoreCartCoupons.mockReturnValue( {
			isApplyingCoupon: true,
			isRemovingCoupon: false,
		} );

		useSelect.mockReturnValue( true );

		const { result } = renderHook( () => useOrderSummaryLoadingState() );
		expect( result.current.isLoading ).toBe( true );
	} );

	it( 'should call useSelect with correct selector function and empty dependency array', () => {
		renderHook( () => useOrderSummaryLoadingState() );

		expect( useSelect ).toHaveBeenCalledWith( expect.any( Function ), [] );

		// Test that the selector function calls the correct store method
		const selectorFunction = useSelect.mock.calls[ 0 ][ 0 ];
		const mockSelect = jest.fn().mockReturnValue( {
			isCalculating: jest.fn().mockReturnValue( false ),
		} );

		selectorFunction( mockSelect );

		expect( mockSelect ).toHaveBeenCalledWith(
			expect.objectContaining( {
				name: 'wc/store/checkout',
			} )
		);
	} );
} );

describe( 'useOrderSummaryRow', () => {
	const mockCart = ( cartFees, isCalculating = false ) => {
		useStoreCart.mockReturnValue( {
			cartIsLoading: false,
			isLoadingRates: false,
			hasPendingItemsOperations: false,
			cartFees,
		} );
		useSelect.mockReturnValue( isCalculating );
	};
	const renderRow = () =>
		renderHook( () =>
			useOrderSummaryRow(
				useStoreCart(),
				( { cartFees } ) => cartFees.length > 0
			)
		);

	beforeEach( () => {
		useStoreCartCoupons.mockReturnValue( {
			isApplyingCoupon: false,
			isRemovingCoupon: false,
		} );
	} );

	afterEach( () => {
		jest.clearAllMocks();
	} );

	it( 'returns the cart while the row shows, and null when it goes', () => {
		mockCart( [ 'fee' ] );
		const { result, rerender } = renderRow();
		expect( result.current.cart.cartFees ).toEqual( [ 'fee' ] );

		mockCart( [] );
		rerender();
		expect( result.current.cart ).toBeNull();
		expect( result.current.wrapperProps.isExiting ).toBe( false );
	} );

	it( 'shows a new row when the skeletons end, not when the data arrives', () => {
		mockCart( [] );
		const { result, rerender } = renderRow();

		mockCart( [ 'fee' ], true );
		rerender();
		expect( result.current.cart ).toBeNull();

		mockCart( [ 'fee' ] );
		rerender();
		expect( result.current.cart.cartFees ).toEqual( [ 'fee' ] );
	} );
} );
