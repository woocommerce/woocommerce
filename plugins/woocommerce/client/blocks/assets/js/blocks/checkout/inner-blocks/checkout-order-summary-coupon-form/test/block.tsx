import { beforeEach, describe, expect, it, vi } from 'vitest';
const { mockApplyCoupon } = vi.hoisted( () => {
	const mockApplyCoupon = vi.fn();
	return {
		mockApplyCoupon,
	};
} );

/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Block from '../block';

// Mock the settings
vi.mock( '@woocommerce/settings', () => {
	const mock = {
		getSetting: vi.fn( ( setting, defaultValue ) => {
			if ( setting === 'couponsEnabled' ) {
				return true;
			}
			return defaultValue;
		} ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );

// Mock the hook

vi.mock( '@woocommerce/base-context/hooks', () => {
	const mock = {
		useStoreCartCoupons: vi.fn( () => ( {
			applyCoupon: mockApplyCoupon,
			isApplyingCoupon: false,
		} ) ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );

// Mock TotalsCoupon component
vi.mock( '@woocommerce/base-components/cart-checkout', () => {
	const mock = {
		TotalsCoupon: vi.fn( ( { isLoading, instanceId } ) => (
			<div data-testid="totals-coupon">
				<span>Coupon Form</span>
				<span data-testid="instance-id">{ instanceId }</span>
				<span data-testid="is-loading">{ isLoading.toString() }</span>
			</div>
		) ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );

// Mock TotalsWrapper component
vi.mock( '@woocommerce/blocks-components', () => {
	const mock = {
		TotalsWrapper: vi.fn( ( { children, className } ) => (
			<div data-testid="totals-wrapper" className={ className }>
				{ children }
			</div>
		) ),
	};
	return Object.defineProperties(
		{
			default: mock,
		},
		Object.getOwnPropertyDescriptors( mock )
	);
} );
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
	it( 'does not render when coupons are disabled', async () => {
		// eslint-disable-next-line @typescript-eslint/no-var-requires -- Required for mocking
		const getSetting = ( await import( '@woocommerce/settings' ) )
			.getSetting;
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
	it( 'passes correct context to useStoreCartCoupons hook', async () => {
		const useStoreCartCoupons =
			// eslint-disable-next-line @typescript-eslint/no-var-requires -- Required for mocking
			( await import( '@woocommerce/base-context/hooks' ) )
				.useStoreCartCoupons;
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
	it( 'integrates applyCoupon function from hook with TotalsCoupon', async () => {
		const TotalsCoupon =
			// eslint-disable-next-line @typescript-eslint/no-var-requires -- Required for mocking
			( await import( '@woocommerce/base-components/cart-checkout' ) )
				.TotalsCoupon;
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
	it( 'passes loading state from hook to TotalsCoupon', async () => {
		const useStoreCartCoupons =
			// eslint-disable-next-line @typescript-eslint/no-var-requires -- Required for mocking
			( await import( '@woocommerce/base-context/hooks' ) )
				.useStoreCartCoupons;

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
