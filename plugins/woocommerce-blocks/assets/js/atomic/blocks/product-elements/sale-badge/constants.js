/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { percent, Icon } from '@wordpress/icons';

export const BLOCK_TITLE = __(
	'On-Sale Badge',
	'woo-gutenberg-products-block'
);
export const BLOCK_ICON = (
	<Icon icon={ percent } className="wc-block-editor-components-block-icon" />
);
export const BLOCK_DESCRIPTION = __(
	'Displays an on-sale badge if the product is on-sale.',
	'woo-gutenberg-products-block'
);
