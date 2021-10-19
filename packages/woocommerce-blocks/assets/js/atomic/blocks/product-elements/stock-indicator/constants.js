/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { box, Icon } from '@woocommerce/icons';

export const BLOCK_TITLE = __(
	'Product Stock Indicator',
	'woocommerce'
);
export const BLOCK_ICON = <Icon srcElement={ box } />;
export const BLOCK_DESCRIPTION = __(
	'Display product stock status.',
	'woocommerce'
);
