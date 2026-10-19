/**
 * External dependencies
 */
import { Icon } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { heading } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import { registerElementVariation } from './utils';

export const CORE_NAME = 'core/post-title';
export const VARIATION_NAME = 'woocommerce/product-query/product-title';

registerElementVariation( CORE_NAME, {
	blockDescription: __( 'Display the title of a product.', 'woocommerce' ),
	blockIcon: <Icon icon={ heading } />,
	blockTitle: __( 'Product Title', 'woocommerce' ),
	variationName: VARIATION_NAME,
	scope: [ 'block' ],
} );
