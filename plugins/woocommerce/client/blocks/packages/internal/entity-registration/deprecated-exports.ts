/**
 * External dependencies
 */
import deprecated from '@wordpress/deprecated';

/**
 * Internal dependencies
 */
import {
	isExternalProduct as isExternalProductInternal,
	isProductResponseItem as isProductResponseItemInternal,
	useProduct as useProductInternal,
} from '../entities';

const deprecationNoticesShown = new Set< string >();

const showDeprecationNotice = ( functionName: string ) => {
	if ( deprecationNoticesShown.has( functionName ) ) {
		return;
	}

	deprecated( `wc.wcEntities.${ functionName }()`, {
		since: '11.1.0',
		plugin: 'WooCommerce',
		hint: 'The wc.wcEntities global is deprecated and will be removed in a future release.',
	} );
	deprecationNoticesShown.add( functionName );
};

/**
 * @deprecated Since WooCommerce 11.1.0. The wc.wcEntities global will be
 * removed in a future release.
 */
export const useProduct: typeof useProductInternal = ( postId ) => {
	showDeprecationNotice( 'useProduct' );
	return useProductInternal( postId );
};

/**
 * @deprecated Since WooCommerce 11.1.0. The wc.wcEntities global will be
 * removed in a future release.
 */
export const isExternalProduct: typeof isExternalProductInternal = (
	product
) => {
	showDeprecationNotice( 'isExternalProduct' );
	return isExternalProductInternal( product );
};

/**
 * @deprecated Since WooCommerce 11.1.0. The wc.wcEntities global will be
 * removed in a future release.
 */
export const isProductResponseItem: typeof isProductResponseItemInternal = (
	product
) => {
	showDeprecationNotice( 'isProductResponseItem' );
	return isProductResponseItemInternal( product );
};
