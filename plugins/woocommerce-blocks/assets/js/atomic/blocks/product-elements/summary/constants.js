/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { notes, Icon } from '@woocommerce/icons';

export const BLOCK_TITLE = __(
	'Product Summary',
	'woo-gutenberg-products-block'
);
export const BLOCK_ICON = <Icon srcElement={ notes } />;
export const BLOCK_DESCRIPTION = __(
	'Display a short description about a product.',
	'woo-gutenberg-products-block'
);
