/**
 * External dependencies
 */
import { TotalsDiscount } from '@woocommerce/base-components/cart-checkout';
import { TotalsWrapper } from '@woocommerce/blocks-components';
import { getCurrencyFromPriceResponse } from '@woocommerce/price-format';
import {
	useStoreCartCoupons,
	useStoreCart,
	useOrderSummaryLoadingState,
	useOrderSummaryRow,
} from '@woocommerce/base-context/hooks';
import { ExperimentalDiscountsMeta } from '@woocommerce/blocks-checkout';

const DiscountSlotFill = (): JSX.Element => {
	// Prepare props to pass to the ExperimentalOrderMeta slot fill. We need to pluck out receiveCart.

	const { extensions, receiveCart, ...cart } = useStoreCart();
	const discountsSlotFillProps = {
		extensions,
		cart,
		context: 'woocommerce/cart',
	};

	return <ExperimentalDiscountsMeta.Slot { ...discountsSlotFillProps } />;
};

const Block = ( { className }: { className: string } ): JSX.Element => {
	const { cart, wrapperProps } = useOrderSummaryRow(
		useStoreCart(),
		( { cartCoupons } ) => cartCoupons.length > 0
	);
	const { removeCoupon, isRemovingCoupon } = useStoreCartCoupons( 'wc/cart' );
	const { isLoading } = useOrderSummaryLoadingState();

	// Hide all but the slot/fill if there are no coupons to show.
	if ( ! cart ) {
		return <DiscountSlotFill />;
	}

	const totalsCurrency = getCurrencyFromPriceResponse( cart.cartTotals );

	return (
		<>
			<TotalsWrapper className={ className } { ...wrapperProps }>
				<TotalsDiscount
					cartCoupons={ cart.cartCoupons }
					currency={ totalsCurrency }
					isRemovingCoupon={ isRemovingCoupon }
					removeCoupon={ removeCoupon }
					values={ cart.cartTotals }
					isLoading={ isLoading }
				/>
			</TotalsWrapper>
			<DiscountSlotFill />
		</>
	);
};

export default Block;
