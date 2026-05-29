/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Block from '../block';
import { TotalsCoupon } from '@woocommerce/base-components/cart-checkout';

jest.mock( '@woocommerce/settings', () => ( {
	getSetting: jest.fn( ( setting, defaultValue ) => {
		if ( setting === 'couponsEnabled' ) {
			return true;
		}
		return defaultValue;
	} ),
} ) );

const mockApplyCoupon = jest.fn();
jest.mock( '@woocommerce/base-context/hooks', () => ( {
	useStoreCartCoupons: jest.fn( () => ( {
		applyCoupon: mockApplyCoupon,
		isApplyingCoupon: false,
	} ) ),
} ) );

jest.mock( '@woocommerce/base-components/cart-checkout', () => ( {
	TotalsCoupon: jest.fn( ( { isLoading, instanceId, displayCouponForm } ) => (
		<div data-testid="totals-coupon">
			<span>Coupon Form</span>
			<span data-testid="instance-id">{ instanceId }</span>
			<span data-testid="is-loading">{ String( isLoading ) }</span>
			<span data-testid="display-coupon-form">
				{ String( displayCouponForm ) }
			</span>
		</div>
	) ),
} ) );

jest.mock( '@woocommerce/blocks-components', () => ( {
	TotalsWrapper: jest.fn( ( { children, className } ) => (
		<div data-testid="totals-wrapper" className={ className }>
			{ children }
		</div>
	) ),
} ) );

const MockTotalsCoupon = jest.mocked( TotalsCoupon );

describe( 'Cart Order Summary Coupon Form Block', () => {
	beforeEach( () => {
		mockApplyCoupon.mockClear();
		MockTotalsCoupon.mockClear();
	} );

	it( 'renders coupon form when coupons are enabled', () => {
		render( <Block className="" /> );

		expect( screen.getByText( 'Coupon Form' ) ).toBeInTheDocument();
		expect( screen.getByTestId( 'totals-wrapper' ) ).toBeInTheDocument();
	} );

	it( 'passes displayCouponForm as true by default', () => {
		render( <Block className="" /> );

		expect( MockTotalsCoupon ).toHaveBeenCalledWith(
			expect.objectContaining( {
				displayCouponForm: true,
			} ),
			expect.anything()
		);
	} );

	it( 'passes displayCouponForm={false} when explicitly set', () => {
		render( <Block className="" displayCouponForm={ false } /> );

		expect( MockTotalsCoupon ).toHaveBeenCalledWith(
			expect.objectContaining( {
				displayCouponForm: false,
			} ),
			expect.anything()
		);
	} );

	it( 'does not render when coupons are disabled', () => {
		// eslint-disable-next-line @typescript-eslint/no-var-requires -- Required for mocking
		const getSetting = require( '@woocommerce/settings' ).getSetting;
		getSetting.mockImplementation(
			( setting: string, defaultValue: unknown ) => {
				if ( setting === 'couponsEnabled' ) return false;
				return defaultValue;
			}
		);

		const { container } = render( <Block className="" /> );
		expect( container.firstChild ).toBeNull();

		getSetting.mockImplementation(
			( setting: string, defaultValue: unknown ) => {
				if ( setting === 'couponsEnabled' ) return true;
				return defaultValue;
			}
		);
	} );

	it( 'calls useStoreCartCoupons with cart context', () => {
		const useStoreCartCoupons =
			// eslint-disable-next-line @typescript-eslint/no-var-requires -- Required for mocking
			require( '@woocommerce/base-context/hooks' ).useStoreCartCoupons;

		render( <Block className="" /> );

		expect( useStoreCartCoupons ).toHaveBeenCalledWith( 'wc/cart' );
	} );

	it( 'passes applyCoupon and instanceId to TotalsCoupon', () => {
		render( <Block className="" /> );

		expect( MockTotalsCoupon ).toHaveBeenCalledWith(
			expect.objectContaining( {
				onSubmit: mockApplyCoupon,
				instanceId: 'coupon',
				isLoading: false,
			} ),
			expect.anything()
		);
	} );
} );
