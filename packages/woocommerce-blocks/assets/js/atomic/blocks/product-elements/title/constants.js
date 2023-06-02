/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { bookmark, Icon } from '@woocommerce/icons';

export const BLOCK_TITLE = __(
	'Product Title',
	'woocommerce'
);
export const BLOCK_ICON = <Icon srcElement={ bookmark } />;
export const BLOCK_DESCRIPTION = __(
	'Display the title of a product.',
	'woocommerce'
);
