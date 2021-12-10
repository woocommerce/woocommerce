/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { star, Icon } from '@woocommerce/icons';

export const BLOCK_TITLE = __(
	'Product Rating',
	'woo-gutenberg-products-block'
);
export const BLOCK_ICON = (
	<Icon
		srcElement={ star }
		className="wc-block-editor-components-block-icon"
	/>
);
export const BLOCK_DESCRIPTION = __(
	'Display the average rating of a product.',
	'woo-gutenberg-products-block'
);
