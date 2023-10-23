/**
 * External dependencies
 */
import { Cart, isObject, objectHasProp } from '@woocommerce/types';
import { select } from '@wordpress/data';
import { getSetting } from '@woocommerce/settings';

/**
 * Internal dependencies
 */
import { STORE_KEY as CART_STORE_KEY } from '../../../data/cart/constants';

declare global {
	interface Window {
		// eslint-disable-next-line @typescript-eslint/naming-convention
		_wca: {
			// eslint-disable-next-line @typescript-eslint/ban-types
			push: ( properties: Record< string, unknown > ) => void;
		};
	}
}

interface StorePageDetails {
	id: number;
	title: string;
	permalink: string;
}

interface StorePages {
	checkout: StorePageDetails;
	cart: StorePageDetails;
	myaccount: StorePageDetails;
	privacy: StorePageDetails;
	shop: StorePageDetails;
	terms: StorePageDetails;
}

/**
 * Check if the _wca object is valid and has a push property that is a function.
 *
 * @param  wca {unknown} Object that might be a Jetpack WooCommerce Analytics object.
 */
// eslint-disable-next-line @typescript-eslint/ban-types
const isValidWCA = (
	wca: unknown
): wca is { push: ( properties: Record< string, unknown > ) => void } => {
	if ( ! isObject( wca ) || ! objectHasProp( wca, 'push' ) ) {
		return false;
	}
	return typeof wca.push === 'function';
};

const registerActions = (): void => {
	if ( ! isValidWCA( window._wca ) ) {
		// eslint-disable-next-line no-useless-return
		return;
	}

	// We will register actions here in a later PR.
};

document.addEventListener( 'DOMContentLoaded', () => {
	registerActions();
} );

export const cleanUrl = ( link: string ) => {
	const url = link.split( '?' )[ 0 ];
	if ( url.charAt( url.length - 1 ) !== '/' ) {
		return url + '/';
	}
	return url;
};

const maybeTrackCheckoutPageView = ( cart: Cart ) => {
	const storePages = getSetting< StorePages >( 'storePages', {} );
	if ( ! objectHasProp( storePages, 'checkout' ) ) {
		return;
	}

	if (
		cleanUrl( storePages?.checkout?.permalink ) !==
		cleanUrl( window.location.href )
	) {
		return;
	}

	if ( ! isValidWCA( window._wca ) ) {
		return;
	}

	const checkoutData = getSetting< Record< string, unknown > >(
		'wc-blocks-jetpack-woocommerce-analytics_cart_checkout_info',
		{}
	);

	window._wca.push( {
		_en: 'woocommerceanalytics_checkout_view',
		products_count: cart.items.length,
		order_value: cart.totals.total_price,
		products: JSON.stringify(
			cart.items.map( ( item ) => {
				return {
					pp: item.totals.line_total,
					pq: item.quantity,
					pi: item.id,
					pn: item.name,
				};
			} )
		),
		...checkoutData,
	} );
};

const maybeTrackCartPageView = ( cart: Cart ) => {
	const storePages = getSetting< StorePages >( 'storePages', {} );
	if ( ! objectHasProp( storePages, 'cart' ) ) {
		return;
	}

	if (
		cleanUrl( storePages?.cart?.permalink ) !==
		cleanUrl( window.location.href )
	) {
		return;
	}

	if ( ! isValidWCA( window._wca ) ) {
		return;
	}

	const checkoutData = getSetting< Record< string, unknown > >(
		'wc-blocks-jetpack-woocommerce-analytics_cart_checkout_info',
		{}
	);

	window._wca.push( {
		_en: 'woocommerceanalytics_cart_view',
		products_count: cart.items.length,
		order_value: cart.totals.total_price,
		products: JSON.stringify(
			cart.items.map( ( item ) => {
				return {
					pp: item.totals.line_total,
					pq: item.quantity,
					pi: item.id,
					pn: item.name,
					pt: item.type,
				};
			} )
		),
		...checkoutData,
	} );
};

const maybeTrackOrderReceivedPageView = () => {
	const orderReceivedProps = getSetting(
		'wc-blocks-jetpack-woocommerce-analytics_order_received_properties',
		false
	);

	if ( ! orderReceivedProps || ! isValidWCA( window._wca ) ) {
		return;
	}

	window._wca.push( {
		_en: 'woocommerceanalytics_order_confirmation_view',
		...orderReceivedProps,
	} );
};

document.addEventListener( 'DOMContentLoaded', () => {
	const store = select( CART_STORE_KEY );

	// If the store doesn't load, we aren't on a cart/checkout block page, so maybe it's order received page.
	if ( ! store ) {
		maybeTrackOrderReceivedPageView();
		return;
	}

	const hasCartLoaded = store.hasFinishedResolution( 'getCartTotals' );
	if ( hasCartLoaded ) {
		maybeTrackCartPageView( store.getCartData() );
		maybeTrackCheckoutPageView( store.getCartData() );
	}
} );
