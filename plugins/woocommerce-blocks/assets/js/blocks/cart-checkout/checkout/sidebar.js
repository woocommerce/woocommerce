/**
 * External dependencies
 */
import {
	SubtotalsItem,
	TotalsFeesItem,
	TotalsCouponCodeInput,
	TotalsDiscountItem,
	TotalsFooterItem,
	TotalsShippingItem,
	TotalsTaxesItem,
} from '@woocommerce/base-components/totals';
import { getCurrencyFromPriceResponse } from '@woocommerce/base-utils';
import {
	COUPONS_ENABLED,
	DISPLAY_CART_PRICES_INCLUDING_TAX,
} from '@woocommerce/block-settings';
import { useStoreCartCoupons } from '@woocommerce/base-hooks';

const CheckoutSidebar = ( {
	cartCoupons = [],
	cartTotals = {},
	shippingRates,
} ) => {
	const {
		applyCoupon,
		removeCoupon,
		isApplyingCoupon,
		isRemovingCoupon,
	} = useStoreCartCoupons();
	const shippingAddress = shippingRates[ 0 ]?.destination;
	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );

	return (
		<>
			<SubtotalsItem currency={ totalsCurrency } values={ cartTotals } />
			<TotalsFeesItem currency={ totalsCurrency } values={ cartTotals } />
			<TotalsDiscountItem
				cartCoupons={ cartCoupons }
				currency={ totalsCurrency }
				isRemovingCoupon={ isRemovingCoupon }
				removeCoupon={ removeCoupon }
				values={ cartTotals }
			/>
			<TotalsShippingItem
				currency={ totalsCurrency }
				shippingAddress={ shippingAddress }
				values={ cartTotals }
			/>
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
