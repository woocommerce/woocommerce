/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { folder, Icon } from '@woocommerce/icons';

export const BLOCK_TITLE: string = __(
	'Product Category List',
	'woo-gutenberg-products-block'
);
export const BLOCK_ICON: JSX.Element = (
	<Icon
		srcElement={ folder }
		className="wc-block-editor-components-block-icon"
	/>
);
export const BLOCK_DESCRIPTION: string = __(
	'Display a list of categories belonging to a product.',
	'woo-gutenberg-products-block'
);
