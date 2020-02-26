/**
 * External dependencies
 */
import { withRestApiHydration } from '@woocommerce/block-hocs';
import { useStoreCart } from '@woocommerce/base-hooks';
import { RawHTML } from '@wordpress/element';
import LoadingMask from '@woocommerce/base-components/loading-mask';
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
		cartErrors,
		cartCoupons,
	} = useStoreCart();

	return (
		<>
			<div className="errors">
				{ // @todo This is a placeholder for error messages - this needs refactoring.
				cartErrors &&
					cartErrors.map( ( error = {}, i ) => (
						<div className="woocommerce-info" key={ 'notice-' + i }>
							{ error.message }
						</div>
					) ) }
			</div>
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
					/>
				</LoadingMask>
			) }
		</>
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
	withRestApiHydration( CartFrontend ),
	getProps
);
