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

describe( 'Checkout Order Summary Coupon Form Block', () => {
	beforeEach( () => {
		mockApplyCoupon.mockClear();
		MockTotalsCoupon.mockClear();
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
		getSetting.mockImplementation(
			( setting: string, defaultValue: unknown ) => {
				if ( setting === 'couponsEnabled' ) return false;
				return defaultValue;
			}
		);

		const { container } = render( <Block /> );
		expect( container.firstChild ).toBeNull();

		getSetting.mockImplementation(
			( setting: string, defaultValue: unknown ) => {
				if ( setting === 'couponsEnabled' ) return true;
				return defaultValue;
			}
		);
	} );

	it( 'passes displayCouponForm as true by default', () => {
		render( <Block /> );

		expect( MockTotalsCoupon ).toHaveBeenCalledWith(
			expect.objectContaining( {
				displayCouponForm: true,
			} ),
			expect.anything()
		);
	} );

	it( 'passes displayCouponForm={false} when explicitly set', () => {
		render( <Block displayCouponForm={ false } /> );

		expect( MockTotalsCoupon ).toHaveBeenCalledWith(
			expect.objectContaining( {
				displayCouponForm: false,
			} ),
			expect.anything()
		);
	} );

	it( 'passes correct props to TotalsCoupon', () => {
		render( <Block /> );

		expect( screen.getByTestId( 'instance-id' ) ).toHaveTextContent(
			'coupon'
		);
		expect( screen.getByTestId( 'is-loading' ) ).toHaveTextContent(
			'false'
		);
	} );

	it( 'passes correct context to useStoreCartCoupons hook', () => {
		const useStoreCartCoupons =
			// eslint-disable-next-line @typescript-eslint/no-var-requires -- Required for mocking
			require( '@woocommerce/base-context/hooks' ).useStoreCartCoupons;

		render( <Block /> );

		expect( useStoreCartCoupons ).toHaveBeenCalledWith( 'wc/checkout' );
	} );

	it( 'passes custom className to TotalsWrapper', () => {
		render( <Block className="custom-coupon-form" /> );

		expect( screen.getByTestId( 'totals-wrapper' ) ).toHaveClass(
			'custom-coupon-form'
		);
	} );

	it( 'passes applyCoupon and instanceId to TotalsCoupon', () => {
		render( <Block /> );

		expect( MockTotalsCoupon ).toHaveBeenCalledWith(
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

		useStoreCartCoupons.mockReturnValueOnce( {
			applyCoupon: mockApplyCoupon,
			isApplyingCoupon: true,
		} );

		render( <Block /> );

		expect( screen.getByTestId( 'is-loading' ) ).toHaveTextContent( 'true' );
	} );
} );
