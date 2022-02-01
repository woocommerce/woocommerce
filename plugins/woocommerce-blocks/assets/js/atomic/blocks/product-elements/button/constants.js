/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, button } from '@wordpress/icons';

export const BLOCK_TITLE = __(
	'Add to Cart Button',
	'woo-gutenberg-products-block'
);
export const BLOCK_ICON = (
	<Icon icon={ button } className="wc-block-editor-components-block-icon" />
);
export const BLOCK_DESCRIPTION = __(
	'Display a call to action button which either adds the product to the cart, or links to the product page.',
	'woo-gutenberg-products-block'
);
