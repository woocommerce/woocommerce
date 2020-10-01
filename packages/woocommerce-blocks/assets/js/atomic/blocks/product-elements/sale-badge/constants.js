/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { tag, Icon } from '@woocommerce/icons';

export const BLOCK_TITLE = __(
	'On-Sale Badge',
	'woocommerce'
);
export const BLOCK_ICON = <Icon srcElement={ tag } />;
export const BLOCK_DESCRIPTION = __(
	'Displays an on-sale badge if the product is on-sale.',
	'woocommerce'
);
