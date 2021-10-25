/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import {
	TotalsCoupon,
	TotalsDiscount,
	TotalsFooterItem,
	TotalsShipping,
} from '@woocommerce/base-components/cart-checkout';
import {
	Subtotal,
	TotalsFees,
	TotalsTaxes,
	TotalsWrapper,
	ExperimentalOrderMeta,
	ExperimentalDiscountsMeta,
} from '@woocommerce/blocks-checkout';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import {
	useStoreCartCoupons,
	useStoreCart,
} from '@woocommerce/base-context/hooks';
import { getSetting } from '@woocommerce/settings';
import Title from '@woocommerce/base-components/title';

/**
 * Internal dependencies
 */

const Block = ( {
	className,
	showRateAfterTaxName = false,
	isShippingCalculatorEnabled = true,
}: {
	className: string;
	showRateAfterTaxName: boolean;
	isShippingCalculatorEnabled: boolean;
} ): JSX.Element => {
	const { cartFees, cartTotals, cartNeedsShipping } = useStoreCart();

	const {
		applyCoupon,
		removeCoupon,
		isApplyingCoupon,
		isRemovingCoupon,
		appliedCoupons,
	} = useStoreCartCoupons();

	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );

	// Prepare props to pass to the ExperimentalOrderMeta slot fill.
	// We need to pluck out receiveCart.
	// eslint-disable-next-line no-unused-vars
	const { extensions, receiveCart, ...cart } = useStoreCart();
	const slotFillProps = {
		extensions,
		cart,
	};

	const discountsSlotFillProps = {
		extensions,
		cart,
	};

	return (
		<div className={ className }>
			<Title headingLevel="2" className="wc-block-cart__totals-title">
				{ __( 'Cart totals', 'woo-gutenberg-products-block' ) }
			</Title>
			<TotalsWrapper>
				<Subtotal currency={ totalsCurrency } values={ cartTotals } />
				<TotalsFees currency={ totalsCurrency } cartFees={ cartFees } />
				<TotalsDiscount
					cartCoupons={ appliedCoupons }
					currency={ totalsCurrency }
					isRemovingCoupon={ isRemovingCoupon }
					removeCoupon={ removeCoupon }
					values={ cartTotals }
				/>
			</TotalsWrapper>
			{ getSetting( 'couponsEnabled', true ) && (
				<TotalsWrapper>
					<TotalsCoupon
						onSubmit={ applyCoupon }
						isLoading={ isApplyingCoupon }
					/>
				</TotalsWrapper>
			) }
			<ExperimentalDiscountsMeta.Slot { ...discountsSlotFillProps } />
			{ cartNeedsShipping && (
				<TotalsWrapper>
					<TotalsShipping
						showCalculator={ isShippingCalculatorEnabled }
						showRateSelector={ true }
						values={ cartTotals }
						currency={ totalsCurrency }
					/>
				</TotalsWrapper>
			) }
			{ ! getSetting( 'displayCartPricesIncludingTax', false ) &&
				parseInt( cartTotals.total_tax, 10 ) > 0 && (
					<TotalsWrapper>
						<TotalsTaxes
							showRateAfterTaxName={ showRateAfterTaxName }
							currency={ totalsCurrency }
							values={ cartTotals }
						/>
					</TotalsWrapper>
				) }
			<TotalsWrapper>
				<TotalsFooterItem
					currency={ totalsCurrency }
					values={ cartTotals }
				/>
			</TotalsWrapper>

			<ExperimentalOrderMeta.Slot { ...slotFillProps } />
		</div>
	);
};

export default Block;
