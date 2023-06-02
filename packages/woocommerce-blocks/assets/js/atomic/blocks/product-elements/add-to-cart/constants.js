/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { cart, Icon } from '@woocommerce/icons';

export const BLOCK_TITLE = __( 'Add to Cart', 'woocommerce' );
export const BLOCK_ICON = <Icon srcElement={ cart } />;
export const BLOCK_DESCRIPTION = __(
	'Displays an add to cart button. Optionally displays other add to cart form elements.',
	'woocommerce'
);
