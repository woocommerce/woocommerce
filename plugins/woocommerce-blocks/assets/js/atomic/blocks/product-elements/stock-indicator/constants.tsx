/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { box, Icon } from '@wordpress/icons';

export const BLOCK_TITLE: string = __(
	'Product Stock Indicator',
	'woocommerce'
);
export const BLOCK_ICON: JSX.Element = (
	<Icon icon={ box } className="wc-block-editor-components-block-icon" />
);
export const BLOCK_DESCRIPTION: string = __(
	'Display product stock status.',
	'woocommerce'
);
