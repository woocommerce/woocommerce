/**
 * External dependencies
 */

import {
	OrderSummary,
	TotalsCoupon,
	TotalsDiscount,
	TotalsFooterItem,
	TotalsShipping,
} from '@woocommerce/base-components/cart-checkout';
import {
	Subtotal,
	TotalsFees,
	TotalsTaxes,
	ExperimentalOrderMeta,
	ExperimentalDiscountsMeta,
	TotalsWrapper,
} from '@woocommerce/blocks-checkout';

import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import { useShippingDataContext } from '@woocommerce/base-context';
import {
	useStoreCartCoupons,
	useStoreCart,
} from '@woocommerce/base-context/hooks';
import { getSetting } from '@woocommerce/settings';

const CheckoutSidebar = ( {
	cartCoupons = [],
	cartItems = [],
	cartFees = [],
	cartTotals = {},
	showRateAfterTaxName = false,
} ) => {
	const {
		applyCoupon,
		removeCoupon,
		isApplyingCoupon,
		isRemovingCoupon,
	} = useStoreCartCoupons();

	const { needsShipping } = useShippingDataContext();
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
		<>
			<TotalsWrapper>
				<OrderSummary cartItems={ cartItems } />
			</TotalsWrapper>
			<TotalsWrapper>
				<Subtotal currency={ totalsCurrency } values={ cartTotals } />
				<TotalsFees currency={ totalsCurrency } cartFees={ cartFees } />
				<TotalsDiscount
					cartCoupons={ cartCoupons }
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
						initialOpen={ false }
						isLoading={ isApplyingCoupon }
					/>
				</TotalsWrapper>
			) }
			<ExperimentalDiscountsMeta.Slot { ...discountsSlotFillProps } />
			{ needsShipping && (
				<TotalsWrapper>
					<TotalsShipping
						showCalculator={ false }
						showRateSelector={ false }
						values={ cartTotals }
						currency={ totalsCurrency }
					/>
				</TotalsWrapper>
			) }
			{ ! getSetting( 'displayCartPricesIncludingTax', false ) &&
				parseInt( cartTotals.total_tax, 10 ) > 0 && (
					<TotalsWrapper>
						<TotalsTaxes
							currency={ totalsCurrency }
							showRateAfterTaxName={ showRateAfterTaxName }
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
		</>
	);
};

export default CheckoutSidebar;
