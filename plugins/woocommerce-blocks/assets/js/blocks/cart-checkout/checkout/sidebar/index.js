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
} from '@woocommerce/blocks-checkout';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import { useShippingDataContext } from '@woocommerce/base-context';
import {
	COUPONS_ENABLED,
	DISPLAY_CART_PRICES_INCLUDING_TAX,
} from '@woocommerce/block-settings';
import {
	useStoreCartCoupons,
	useStoreCart,
} from '@woocommerce/base-context/hooks';

const CheckoutSidebar = ( {
	cartCoupons = [],
	cartItems = [],
	cartFees = [],
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

	// Prepare props to pass to the ExperimentalOrderMeta slot fill.
	// We need to pluck out receiveCart.
	// eslint-disable-next-line no-unused-vars
	const { extensions, receiveCart, ...cart } = useStoreCart();
	const slotFillProps = {
		extensions,
		cart,
	};

	return (
		<>
			<OrderSummary cartItems={ cartItems } />
			<Subtotal currency={ totalsCurrency } values={ cartTotals } />
			<TotalsFees currency={ totalsCurrency } cartFees={ cartFees } />
			<TotalsDiscount
				cartCoupons={ cartCoupons }
				currency={ totalsCurrency }
				isRemovingCoupon={ isRemovingCoupon }
				removeCoupon={ removeCoupon }
				values={ cartTotals }
			/>
			{ needsShipping && (
				<TotalsShipping
					showCalculator={ false }
					showRateSelector={ false }
					values={ cartTotals }
					currency={ totalsCurrency }
				/>
			) }
			{ ! DISPLAY_CART_PRICES_INCLUDING_TAX && (
				<TotalsTaxes
					currency={ totalsCurrency }
					values={ cartTotals }
				/>
			) }
			{ COUPONS_ENABLED && (
				<TotalsCoupon
					onSubmit={ applyCoupon }
					initialOpen={ false }
					isLoading={ isApplyingCoupon }
				/>
			) }
			<TotalsFooterItem
				currency={ totalsCurrency }
				values={ cartTotals }
			/>
			<ExperimentalOrderMeta.Slot { ...slotFillProps } />
		</>
	);
};

export default CheckoutSidebar;
