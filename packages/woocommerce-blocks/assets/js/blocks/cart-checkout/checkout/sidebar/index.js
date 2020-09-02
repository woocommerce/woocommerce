/**
 * External dependencies
 */
import {
	OrderSummary,
	SubtotalsItem,
	TotalsFeesItem,
	TotalsCouponCodeInput,
	TotalsDiscountItem,
	TotalsFooterItem,
	TotalsShippingItem,
	TotalsTaxesItem,
} from '@woocommerce/base-components/cart-checkout';
import { useShippingDataContext } from '@woocommerce/base-context';
import { getCurrencyFromPriceResponse } from '@woocommerce/base-utils';
import {
	COUPONS_ENABLED,
	DISPLAY_CART_PRICES_INCLUDING_TAX,
} from '@woocommerce/block-settings';
import { useStoreCartCoupons } from '@woocommerce/base-hooks';

const CheckoutSidebar = ( {
	cartCoupons = [],
	cartItems = [],
	cartTotals = {},
} ) => {
	const {
		applyCoupon,
		removeCoupon,
		isApplyingCoupon,
		isRemovingCoupon,
	} = useStoreCartCoupons();

	const { needsShipping } = useShippingDataContext();
	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );

	return (
		<>
			<OrderSummary cartItems={ cartItems } />
			<SubtotalsItem currency={ totalsCurrency } values={ cartTotals } />
			<TotalsFeesItem currency={ totalsCurrency } values={ cartTotals } />
			<TotalsDiscountItem
				cartCoupons={ cartCoupons }
				currency={ totalsCurrency }
				isRemovingCoupon={ isRemovingCoupon }
				removeCoupon={ removeCoupon }
				values={ cartTotals }
			/>
			{ needsShipping && (
				<TotalsShippingItem
					currency={ totalsCurrency }
					noResultsMessage={ null }
					isCheckout={ true }
					showCalculator={ false }
					values={ cartTotals }
				/>
			) }
			{ ! DISPLAY_CART_PRICES_INCLUDING_TAX && (
				<TotalsTaxesItem
					currency={ totalsCurrency }
					values={ cartTotals }
				/>
			) }
			{ COUPONS_ENABLED && (
				<TotalsCouponCodeInput
					onSubmit={ applyCoupon }
					initialOpen={ false }
					isLoading={ isApplyingCoupon }
				/>
			) }
			<TotalsFooterItem
				currency={ totalsCurrency }
				values={ cartTotals }
			/>
		</>
	);
};

export default CheckoutSidebar;
