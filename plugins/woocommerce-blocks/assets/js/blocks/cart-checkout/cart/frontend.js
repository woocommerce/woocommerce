/**
 * External dependencies
 */
import { withRestApiHydration } from '@woocommerce/block-hocs';

/**
 * Internal dependencies
 */
import FullCart from './full-cart';
import renderFrontend from '../../../utils/render-frontend.js';

const isCartEmpty = false; // @todo check if the cart has some products
const selector = '.wp-block-woocommerce-cart';

if ( ! isCartEmpty ) {
	const getProps = () => {
		return {
			attributes: {},
		};
	};

	renderFrontend( selector, withRestApiHydration( FullCart ), getProps );
} else {
	const containers = document.querySelectorAll( selector );

	if ( containers.length ) {
		// Use Array.forEach for IE11 compatibility.
		Array.prototype.forEach.call( containers, ( el ) => {
			el.classList.remove( 'is-loading' );
		} );
	}
}
