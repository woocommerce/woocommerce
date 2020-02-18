/**
 * External dependencies
 */
import { withRestApiHydration } from '@woocommerce/block-hocs';
import { useStoreCart } from '@woocommerce/base-hooks';
import { RawHTML } from '@wordpress/element';

/**
 * Internal dependencies
 */
import FullCart from './full-cart';
import renderFrontend from '../../../utils/render-frontend.js';

/**
 * Wrapper component to supply API data and show empty cart view as needed.
 */
const CartFrontend = ( { emptyCart } ) => {
	const { cartData, isLoading } = useStoreCart();

	if ( isLoading ) {
		return null;
	}

	const cartItems = cartData.items;
	const isCartEmpty = cartItems.length <= 0;

	return isCartEmpty ? (
		<RawHTML>{ emptyCart }</RawHTML>
	) : (
		<FullCart cartItems={ cartItems } cartTotals={ cartData.totals } />
	);
};

const getProps = ( el ) => ( {
	emptyCart: el.innerHTML,
} );

renderFrontend(
	'.wp-block-woocommerce-cart',
	withRestApiHydration( CartFrontend ),
	getProps
);
