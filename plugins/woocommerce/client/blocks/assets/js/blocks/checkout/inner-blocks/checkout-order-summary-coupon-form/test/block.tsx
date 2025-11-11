/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Block from '../block';

// Mock the settings
jest.mock( '@woocommerce/settings', () => ( {
	getSetting: jest.fn( ( setting, defaultValue ) => {
		if ( setting === 'couponsEnabled' ) {
			return true;
		}
		return defaultValue;
	} ),
} ) );

// Mock the hook
const mockApplyCoupon = jest.fn();
jest.mock( '@woocommerce/base-context/hooks', () => ( {
	useStoreCartCoupons: jest.fn( () => ( {
		applyCoupon: mockApplyCoupon,
		isApplyingCoupon: false,
	} ) ),
} ) );

// Mock TotalsCoupon component
jest.mock( '@woocommerce/base-components/cart-checkout', () => ( {
	TotalsCoupon: jest.fn( ( { isLoading, instanceId } ) => (
		<div data-testid="totals-coupon">
			<span>Coupon Form</span>
			<span data-testid="instance-id">{ instanceId }</span>
			<span data-testid="is-loading">{ isLoading.toString() }</span>
		</div>
	) ),
} ) );

// Mock TotalsWrapper component
jest.mock( '@woocommerce/blocks-components', () => ( {
	TotalsWrapper: jest.fn( ( { children, className } ) => (
		<div data-testid="totals-wrapper" className={ className }>
			{ children }
		</div>
	) ),
} ) );

describe( 'Checkout Order Summary Coupon Form Block', () => {
	beforeEach( () => {
		mockApplyCoupon.mockClear();
	} );

	it( 'renders coupon form when coupons are enabled', () => {
		render( <Block /> );

		expect( screen.getByText( 'Coupon Form' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'totals-coupon' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'totals-wrapper' ) ).toBeInTheDocument();
	} );

	it( 'does not render when coupons are disabled', () => {
		// eslint-disable-next-line @typescript-eslint/no-var-requires -- Required for mocking
		const getSetting = require( '@woocommerce/settings' ).getSetting;
		getSetting.mockImplementation( ( setting, defaultValue ) => {
			if ( setting === 'couponsEnabled' ) {
				return false;
			}
			return defaultValue;
		} );

		const { container } = render( <Block /> );
		expect( container.firstChild ).toBeNull();

		// Reset for other tests
		getSetting.mockImplementation( ( setting, defaultValue ) => {
			if ( setting === 'couponsEnabled' ) {
				return true;
			}
			return defaultValue;
		} );
	} );

	it( 'passes correct props to TotalsCoupon', () => {
		render( <Block /> );

		// Verify instanceId is passed correctly
		expect( screen.getByTestId( 'instance-id' ) ).toHaveTextContent(
			'coupon'
		);

		// Verify loading state is passed correctly
		expect( screen.getByTestId( 'is-loading' ) ).toHaveTextContent(
			'false'
		);
	} );

	it( 'passes correct context to useStoreCartCoupons hook', () => {
		const useStoreCartCoupons =
			// eslint-disable-next-line @typescript-eslint/no-var-requires -- Required for mocking
			require( '@woocommerce/base-context/hooks' ).useStoreCartCoupons;

		render( <Block /> );

		// Verify the hook was called with checkout context
		expect( useStoreCartCoupons ).toHaveBeenCalledWith( 'wc/checkout' );
	} );

	it( 'passes custom className to TotalsWrapper', () => {
		const customClass = 'custom-coupon-form';
		render( <Block className={ customClass } /> );

		const wrapper = screen.getByTestId( 'totals-wrapper' );
		expect( wrapper ).toHaveClass( customClass );
	} );

	it( 'integrates applyCoupon function from hook with TotalsCoupon', () => {
		const TotalsCoupon =
			// eslint-disable-next-line @typescript-eslint/no-var-requires -- Required for mocking
			require( '@woocommerce/base-components/cart-checkout' ).TotalsCoupon;

		render( <Block /> );

		// Verify TotalsCoupon receives applyCoupon function
		expect( TotalsCoupon ).toHaveBeenCalledWith(
			expect.objectContaining( {
				onSubmit: mockApplyCoupon,
				instanceId: 'coupon',
				isLoading: false,
			} ),
			expect.anything()
		);
	} );

	it( 'passes loading state from hook to TotalsCoupon', () => {
		const useStoreCartCoupons =
			// eslint-disable-next-line @typescript-eslint/no-var-requires -- Required for mocking
			require( '@woocommerce/base-context/hooks' ).useStoreCartCoupons;

		// Mock loading state
		useStoreCartCoupons.mockReturnValue( {
			applyCoupon: mockApplyCoupon,
			isApplyingCoupon: true,
		} );

		render( <Block /> );

		// Verify loading state is reflected
		expect( screen.getByTestId( 'is-loading' ) ).toHaveTextContent(
			'true'
		);
	} );
} );
