/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { applyCheckoutFilter } from '@woocommerce/blocks-checkout';
import { getSetting } from '@woocommerce/settings';
import { CartResponseTotalsItem } from '@woocommerce/type-defs/cart-response';
import type { LoadingMaskProps } from '@woocommerce/base-components/loading-mask';
/**
 * Internal dependencies
 */
import TotalsDiscount, { TotalsDiscountProps } from '..';
import { RemovableChipProps } from '../../../../../../../../packages/components/chip/removable-chip';
import { TotalsItemProps } from '../../../../../../../../packages/components/totals/item';

// Mock external dependencies
jest.mock( '@woocommerce/settings', () => ( {
	getSetting: jest
		.fn()
		.mockImplementation( ( settingName, fallback ) => fallback ),
	getSettingWithCoercion: jest
		.fn()
		.mockImplementation( ( settingName, fallback ) => fallback ),
} ) );

jest.mock( '@woocommerce/blocks-checkout', () => ( {
	applyCheckoutFilter: jest.fn(),
} ) );

// Mock the core components to verify they're called with correct props
const mockRemovableChip = jest.fn();
const mockTotalsItem = jest.fn();

// Mock LoadingMask component
jest.mock(
	'@woocommerce/base-components/loading-mask',
	() => ( props: LoadingMaskProps ) =>
		(
			<div
				className="wc-block-components-loading-mask"
				data-testid={ props.isLoading ? 'loading-mask' : 'not-loading' }
			>
				{ props.isLoading && (
					<span className="screen-reader-text">
						{ props.screenReaderLabel }
					</span>
				) }
				<div
					className="wc-block-components-loading-mask__children"
					aria-hidden={ props.isLoading ? 'true' : 'false' }
				>
					{ props.children }
				</div>
			</div>
		)
);

jest.mock( '@woocommerce/blocks-components', () => ( {
	RemovableChip: ( props: RemovableChipProps ) => {
		mockRemovableChip( props );
		return (
			<div data-testid="removable-chip">
				<span>{ props.text }</span>
				<button
					onClick={ props.onRemove }
					disabled={ props.disabled }
					aria-label={ props.ariaLabel }
				>
					Remove
				</button>
			</div>
		);
	},
	TotalsItem: ( props: TotalsItemProps ) => {
		mockTotalsItem( props );
		return (
			<div data-testid="totals-item">
				<span>{ props.label }</span>
				{ props.showSkeleton ? (
					<div data-testid="skeleton">Loading...</div>
				) : (
					<span>{ props.value }</span>
				) }
				{ props.description && <div>{ props.description }</div> }
			</div>
		);
	},
} ) );

describe( 'TotalsDiscount', () => {
	const defaultProps: TotalsDiscountProps = {
		cartCoupons: [],
		currency: {
			code: 'USD',
			symbol: '$',
			minorUnit: 2,
			decimalSeparator: '.',
			prefix: '$',
			suffix: '',
			thousandSeparator: ',',
		},
		isRemovingCoupon: false,
		removeCoupon: jest.fn(),
		values: {
			total_discount: '0',
			total_discount_tax: '0',
		},
	};
	const defaultCouponTotals: CartResponseTotalsItem = {
		total_discount: '0',
		total_discount_tax: '0',
		currency_code: 'USD',
		currency_symbol: '$',
		currency_minor_unit: 0,
		currency_decimal_separator: '',
		currency_thousand_separator: '',
		currency_prefix: '',
		currency_suffix: '',
	};

	beforeEach( () => {
		jest.clearAllMocks();
		// Clear component mocks
		mockRemovableChip.mockClear();
		mockTotalsItem.mockClear();
		// Default mock implementations
		( applyCheckoutFilter as jest.Mock ).mockImplementation(
			( { defaultValue } ) => defaultValue
		);
		( getSetting as jest.Mock ).mockReturnValue( false );
	} );

	describe( 'Component visibility', () => {
		it( 'should return null when there are no discounts and no coupons', () => {
			const { container } = render(
				<TotalsDiscount { ...defaultProps } />
			);
			expect( container.firstChild ).toBeNull();
			expect( mockTotalsItem ).not.toHaveBeenCalled();
		} );

		it( 'should render TotalsItem when there is a discount value', () => {
			const props = {
				...defaultProps,
				values: {
					total_discount: '500',
					total_discount_tax: '0',
				},
			};
			render( <TotalsDiscount { ...props } /> );

			expect( mockTotalsItem ).toHaveBeenCalledWith(
				expect.objectContaining( {
					className: 'wc-block-components-totals-discount',
					currency: props.currency,
					label: 'Discount',
					value: -500, // Should be negative value
				} )
			);
		} );

		it( 'should render with coupons when there are coupons but no discount', () => {
			const props: TotalsDiscountProps = {
				...defaultProps,
				cartCoupons: [
					{
						code: 'TEST5',
						label: 'Test Coupon',
						totals: {
							total_discount: '0',
							total_discount_tax: '0',
							currency_code: 'USD',
							currency_symbol: '$',
							currency_minor_unit: 0,
							currency_decimal_separator: '',
							currency_thousand_separator: '',
							currency_prefix: '',
							currency_suffix: '',
						},
					},
				],
			};
			render( <TotalsDiscount { ...props } /> );

			// Verify the checkout filter is called correctly
			expect( applyCheckoutFilter ).toHaveBeenCalledWith( {
				arg: { context: 'summary' },
				filterName: 'coupons',
				defaultValue: props.cartCoupons,
			} );

			expect( mockTotalsItem ).toHaveBeenCalledWith(
				expect.objectContaining( {
					label: 'Coupons',
					value: '-',
					description: expect.anything(), // LoadingMask with RemovableChip is rendered
				} )
			);

			// Since the RemovableChip is nested inside LoadingMask description,
			// we can verify it exists in the description prop
			const totalsItemCall = mockTotalsItem.mock.calls[ 0 ][ 0 ];
			expect( totalsItemCall.description ).toBeDefined();

			// Check that the coupon chip is rendered (even if our mock tracking doesn't catch it)
			expect( screen.getByText( 'Test Coupon' ) ).toBeInTheDocument();
		} );
	} );

	describe( 'Discount calculations', () => {
		it( 'should pass correct value without tax when displayCartPricesIncludingTax is false', () => {
			( getSetting as jest.Mock ).mockReturnValue( false );
			const props = {
				...defaultProps,
				values: {
					total_discount: '1000',
					total_discount_tax: '100',
				},
			};
			render( <TotalsDiscount { ...props } /> );

			expect( mockTotalsItem ).toHaveBeenCalledWith(
				expect.objectContaining( {
					value: -1000, // Should use total_discount only
				} )
			);
		} );

		it( 'should pass correct value with tax when displayCartPricesIncludingTax is true', () => {
			( getSetting as jest.Mock ).mockReturnValue( true );
			const props = {
				...defaultProps,
				values: {
					total_discount: '1000',
					total_discount_tax: '100',
				},
			};
			render( <TotalsDiscount { ...props } /> );

			expect( mockTotalsItem ).toHaveBeenCalledWith(
				expect.objectContaining( {
					value: -1100, // Should use total_discount + total_discount_tax
				} )
			);
		} );

		it( 'should handle string values correctly', () => {
			const props = {
				...defaultProps,
				values: {
					total_discount: '999',
					total_discount_tax: '99',
				},
			};
			render( <TotalsDiscount { ...props } /> );

			expect( mockTotalsItem ).toHaveBeenCalledWith(
				expect.objectContaining( {
					value: -999,
				} )
			);
		} );
	} );

	describe( 'Coupon display', () => {
		it( 'should render RemovableChip for each coupon', () => {
			const props = {
				...defaultProps,
				cartCoupons: [
					{
						code: 'COUPON1',
						label: 'First Coupon',
						totals: {
							...defaultCouponTotals,
							total_discount: '500',
							total_discount_tax: '0',
						},
					},
					{
						code: 'COUPON2',
						label: 'Second Coupon',
						totals: {
							...defaultCouponTotals,
							total_discount: '300',
							total_discount_tax: '0',
						},
					},
				],
				values: {
					total_discount: '800',
					total_discount_tax: '0',
				},
			};
			render( <TotalsDiscount { ...props } /> );

			// Verify both coupons are rendered in the DOM
			expect( screen.getByText( 'First Coupon' ) ).toBeInTheDocument();
			expect( screen.getByText( 'Second Coupon' ) ).toBeInTheDocument();

			// Verify TotalsItem was called with description containing the coupons
			expect( mockTotalsItem ).toHaveBeenCalledWith(
				expect.objectContaining( {
					label: 'Discount',
					value: -800,
					description: expect.anything(),
				} )
			);
		} );

		it( 'should apply checkout filter to coupons', () => {
			const filteredCoupons = [
				{
					code: 'FILTERED',
					label: 'Filtered Coupon',
					totals: { total_discount: '100', total_discount_tax: '0' },
				},
			];
			( applyCheckoutFilter as jest.Mock ).mockReturnValue(
				filteredCoupons
			);

			const props = {
				...defaultProps,
				cartCoupons: [
					{
						code: 'ORIGINAL',
						label: 'Original Coupon',
						totals: {
							...defaultCouponTotals,
							total_discount: '100',
							total_discount_tax: '0',
						},
					},
				],
				values: {
					total_discount: '100',
					total_discount_tax: '0',
				},
			};
			render( <TotalsDiscount { ...props } /> );

			expect( applyCheckoutFilter ).toHaveBeenCalledWith( {
				arg: { context: 'summary' },
				filterName: 'coupons',
				defaultValue: props.cartCoupons,
			} );

			// Should render the filtered coupon, not the original
			expect( screen.getByText( 'Filtered Coupon' ) ).toBeInTheDocument();
			expect(
				screen.queryByText( 'Original Coupon' )
			).not.toBeInTheDocument();
		} );
	} );

	describe( 'Coupon removal', () => {
		it( 'should call removeCoupon when remove button is clicked', async () => {
			const user = userEvent.setup();
			const mockRemoveCoupon = jest.fn();
			const props = {
				...defaultProps,
				cartCoupons: [
					{
						code: 'REMOVE_ME',
						label: 'Removable Coupon',
						totals: {
							...defaultCouponTotals,
							total_discount: '500',
							total_discount_tax: '0',
						},
					},
				],
				values: {
					total_discount: '500',
					total_discount_tax: '0',
				},
				removeCoupon: mockRemoveCoupon,
			};
			render( <TotalsDiscount { ...props } /> );

			// Find the remove button by its aria-label
			const removeButton = screen.getByLabelText(
				'Remove coupon "Removable Coupon"'
			);
			await user.click( removeButton );

			expect( mockRemoveCoupon ).toHaveBeenCalledWith( 'REMOVE_ME' );
			expect( mockRemoveCoupon ).toHaveBeenCalledTimes( 1 );
		} );

		it( 'should disable remove buttons when isRemovingCoupon is true', () => {
			const props = {
				...defaultProps,
				cartCoupons: [
					{
						code: 'COUPON1',
						label: 'Test Coupon',
						totals: {
							...defaultCouponTotals,
							total_discount: '500',
							total_discount_tax: '0',
						},
					},
				],
				values: {
					total_discount: '500',
					total_discount_tax: '0',
				},
				isRemovingCoupon: true,
			};
			render( <TotalsDiscount { ...props } /> );

			const removeButton = screen.getByLabelText(
				'Remove coupon "Test Coupon"'
			);
			expect( removeButton ).toBeDisabled();
		} );

		it( 'should not call removeCoupon when button is disabled', async () => {
			const user = userEvent.setup();
			const mockRemoveCoupon = jest.fn();
			const props = {
				...defaultProps,
				cartCoupons: [
					{
						code: 'COUPON1',
						label: 'Test Coupon',
						totals: {
							...defaultCouponTotals,
							total_discount: '500',
							total_discount_tax: '0',
						},
					},
				],
				values: {
					total_discount: '500',
					total_discount_tax: '0',
				},
				removeCoupon: mockRemoveCoupon,
				isRemovingCoupon: true,
			};
			render( <TotalsDiscount { ...props } /> );

			const removeButton = screen.getByLabelText(
				'Remove coupon "Test Coupon"'
			);
			await user.click( removeButton );

			// Click should not work when button is disabled
			expect( mockRemoveCoupon ).not.toHaveBeenCalled();
		} );
	} );

	describe( 'Loading states', () => {
		it( 'should show skeleton when isLoading is true', () => {
			const props = {
				...defaultProps,
				values: {
					total_discount: '500',
					total_discount_tax: '0',
				},
				isLoading: true,
			};
			render( <TotalsDiscount { ...props } /> );

			// TotalsItem component should show skeleton
			expect( screen.getByTestId( 'skeleton' ) ).toBeInTheDocument();
		} );

		it( 'should show loading mask when removing coupon', () => {
			const props = {
				...defaultProps,
				cartCoupons: [
					{
						code: 'COUPON1',
						label: 'Test Coupon',
						totals: {
							...defaultCouponTotals,
							total_discount: '500',
							total_discount_tax: '0',
						},
					},
				],
				values: {
					total_discount: '500',
					total_discount_tax: '0',
				},
				isRemovingCoupon: true,
			};
			render( <TotalsDiscount { ...props } /> );

			// Verify loading mask is shown with correct state
			expect( screen.getByTestId( 'loading-mask' ) ).toBeInTheDocument();
			expect(
				screen.getByText( 'Removing coupon…' )
			).toBeInTheDocument();
			expect( screen.getByText( 'Test Coupon' ) ).toBeInTheDocument();
		} );

		it( 'should not show loading mask when not removing coupon', () => {
			const props = {
				...defaultProps,
				cartCoupons: [
					{
						code: 'COUPON1',
						label: 'Test Coupon',
						totals: {
							...defaultCouponTotals,
							total_discount: '500',
							total_discount_tax: '0',
						},
					},
				],
				values: {
					total_discount: '500',
					total_discount_tax: '0',
				},
				isRemovingCoupon: false,
			};
			render( <TotalsDiscount { ...props } /> );

			// Verify loading mask shows not loading state
			expect( screen.getByTestId( 'not-loading' ) ).toBeInTheDocument();
			expect( screen.getByText( 'Test Coupon' ) ).toBeInTheDocument();
		} );
	} );

	describe( 'Label text', () => {
		it( 'should show "Discount" label when there is a discount value', () => {
			const props = {
				...defaultProps,
				values: {
					total_discount: '100',
					total_discount_tax: '0',
				},
			};
			render( <TotalsDiscount { ...props } /> );
			expect( screen.getByText( 'Discount' ) ).toBeInTheDocument();
		} );

		it( 'should show "Coupons" label when there is no discount value but coupons exist', () => {
			const props = {
				...defaultProps,
				cartCoupons: [
					{
						code: 'ZERO_DISCOUNT',
						label: 'Zero Discount Coupon',
						totals: {
							...defaultCouponTotals,
							total_discount: '0',
							total_discount_tax: '0',
						},
					},
				],
				values: {
					total_discount: '0',
					total_discount_tax: '0',
				},
			};
			render( <TotalsDiscount { ...props } /> );
			expect( screen.getByText( 'Coupons' ) ).toBeInTheDocument();
		} );
	} );

	describe( 'Edge cases', () => {
		it( 'should handle negative discount values gracefully', () => {
			const props = {
				...defaultProps,
				values: {
					total_discount: '-500',
					total_discount_tax: '0',
				},
			};
			render( <TotalsDiscount { ...props } /> );
			// Negative discount would be double negated, resulting in positive
			expect( mockTotalsItem ).toHaveBeenCalledWith(
				expect.objectContaining( {
					value: 500, // -(-500) = 500
				} )
			);
		} );

		it( 'should handle non-numeric discount values', () => {
			const props = {
				...defaultProps,
				values: {
					total_discount: 'abc',
					total_discount_tax: 'xyz',
				},
			};
			// parseInt('abc') returns NaN, which is falsy, so component shouldn't render
			const { container } = render( <TotalsDiscount { ...props } /> );
			expect( container.firstChild ).toBeNull();
		} );

		it( 'should handle coupons with special characters in labels', () => {
			const props = {
				...defaultProps,
				cartCoupons: [
					{
						code: 'SPECIAL',
						label: '50% off & $5 bonus!',
						totals: {
							...defaultCouponTotals,
							total_discount: '500',
							total_discount_tax: '0',
						},
					},
				],
				values: {
					total_discount: '500',
					total_discount_tax: '0',
				},
			};
			render( <TotalsDiscount { ...props } /> );
			expect(
				screen.getByText( '50% off & $5 bonus!' )
			).toBeInTheDocument();
		} );

		it( 'should handle empty arrays and filter results', () => {
			( applyCheckoutFilter as jest.Mock ).mockReturnValue( [] );
			const props = {
				...defaultProps,
				cartCoupons: [
					{
						code: 'FILTERED_OUT',
						label: 'Filtered Out',
						totals: {
							...defaultCouponTotals,
							total_discount: '100',
							total_discount_tax: '0',
						},
					},
				],
				values: {
					total_discount: '0',
					total_discount_tax: '0',
				},
			};
			const { container } = render( <TotalsDiscount { ...props } /> );
			// No discount and filtered coupons is empty, so should not render
			expect( container.firstChild ).toBeNull();
		} );
	} );
} );
