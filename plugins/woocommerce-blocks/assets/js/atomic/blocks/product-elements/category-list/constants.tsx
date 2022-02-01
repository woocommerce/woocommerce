/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { archive, Icon } from '@wordpress/icons';

export const BLOCK_TITLE: string = __(
	'Product Category List',
	'woo-gutenberg-products-block'
);
export const BLOCK_ICON: JSX.Element = (
	<Icon icon={ archive } className="wc-block-editor-components-block-icon" />
);
export const BLOCK_DESCRIPTION: string = __(
	'Display a list of categories belonging to a product.',
	'woo-gutenberg-products-block'
);
