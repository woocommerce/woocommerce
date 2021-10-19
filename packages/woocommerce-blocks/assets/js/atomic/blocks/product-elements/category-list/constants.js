/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { folder, Icon } from '@woocommerce/icons';

export const BLOCK_TITLE = __(
	'Product Category List',
	'woocommerce'
);
export const BLOCK_ICON = <Icon srcElement={ folder } />;
export const BLOCK_DESCRIPTION = __(
	'Display a list of categories belonging to a product.',
	'woocommerce'
);
