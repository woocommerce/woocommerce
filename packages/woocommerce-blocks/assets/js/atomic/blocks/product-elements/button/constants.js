/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { cart, Icon } from '@woocommerce/icons';

export const BLOCK_TITLE = __(
	'Add to Cart Button',
	'woocommerce'
);
export const BLOCK_ICON = <Icon srcElement={ cart } />;
export const BLOCK_DESCRIPTION = __(
	'Display a call to action button which either adds the product to the cart, or links to the product page.',
	'woocommerce'
);
