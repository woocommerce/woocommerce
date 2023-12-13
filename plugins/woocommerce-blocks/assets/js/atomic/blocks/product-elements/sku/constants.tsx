/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { barcode } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';

export const BLOCK_TITLE: string = __( 'Product SKU', 'woocommerce' );
export const BLOCK_ICON: JSX.Element = (
	<Icon icon={ barcode } className="wc-block-editor-components-block-icon" />
);
export const BLOCK_DESCRIPTION: string = __(
	'Display the SKU of a product.',
	'woocommerce'
);
