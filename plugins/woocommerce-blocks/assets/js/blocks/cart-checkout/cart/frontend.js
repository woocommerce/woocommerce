/**
 * External dependencies
 */
import {
	withRestApiHydration,
	withStoreCartApiHydration,
} from '@woocommerce/block-hocs';
import { useStoreCart } from '@woocommerce/base-hooks';
import { RawHTML } from '@wordpress/element';
import LoadingMask from '@woocommerce/base-components/loading-mask';
import StoreNoticesProvider from '@woocommerce/base-context/store-notices-context';

/**
 * Internal dependencies
 */
import FullCart from './full-cart';
import renderFrontend from '../../../utils/render-frontend.js';

/**
 * Wrapper component to supply API data and show empty cart view as needed.
 */
const CartFrontend = ( {
	emptyCart,
	isShippingCalculatorEnabled,
	isShippingCostHidden,
} ) => {
	const {
		cartItems,
		cartTotals,
		cartIsLoading,
		cartCoupons,
		shippingRates,
	} = useStoreCart();

	return (
		<StoreNoticesProvider context="wc/cart">
			{ ! cartIsLoading && ! cartItems.length ? (
				<RawHTML>{ emptyCart }</RawHTML>
			) : (
				<LoadingMask showSpinner={ true } isLoading={ cartIsLoading }>
					<FullCart
						cartItems={ cartItems }
						cartTotals={ cartTotals }
						cartCoupons={ cartCoupons }
						isShippingCalculatorEnabled={
							isShippingCalculatorEnabled
						}
						isShippingCostHidden={ isShippingCostHidden }
						isLoading={ cartIsLoading }
						shippingRates={ shippingRates }
					/>
				</LoadingMask>
			) }
		</StoreNoticesProvider>
	);
};

const getProps = ( el ) => ( {
	emptyCart: el.innerHTML,
	isShippingCalculatorEnabled:
		el.dataset.isshippingcalculatorenabled === 'true',
	isShippingCostHidden: el.dataset.isshippingcosthidden === 'true',
} );

renderFrontend(
	'.wp-block-woocommerce-cart',
	withStoreCartApiHydration( withRestApiHydration( CartFrontend ) ),
	getProps
);
