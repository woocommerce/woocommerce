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
	useTotalsRow,
} from '@woocommerce/base-context/hooks';
import { ExperimentalDiscountsMeta } from '@woocommerce/blocks-checkout';

const DiscountSlotFill = (): JSX.Element => {
	// Prepare props to pass to the ExperimentalOrderMeta slot fill. We need to pluck out receiveCart.

	const { extensions, receiveCart, ...cart } = useStoreCart();
	const discountsSlotFillProps = {
		extensions,
		cart,
		context: 'woocommerce/checkout',
	};

	return <ExperimentalDiscountsMeta.Slot { ...discountsSlotFillProps } />;
};

const Block = ( { className = '' }: { className?: string } ): JSX.Element => {
	// Matches when `TotalsDiscount` renders anything.
	const { cartTotals, cartCoupons, wrapperProps } = useTotalsRow(
		useStoreCart(),
		className,
		( cart ) =>
			cart.cartCoupons.length > 0 ||
			!! parseInt( cart.cartTotals.total_discount, 10 )
	);
	const { removeCoupon, isRemovingCoupon } =
		useStoreCartCoupons( 'wc/checkout' );
	const { isLoading } = useOrderSummaryLoadingState();
	const totalsCurrency = getCurrencyFromPriceResponse( cartTotals );

	return (
		<>
			<TotalsWrapper { ...wrapperProps }>
				<TotalsDiscount
					cartCoupons={ cartCoupons }
					currency={ totalsCurrency }
					isRemovingCoupon={ isRemovingCoupon }
					removeCoupon={ removeCoupon }
					values={ cartTotals }
					isLoading={ isLoading }
				/>
			</TotalsWrapper>
			<DiscountSlotFill />
		</>
	);
};

export default Block;
