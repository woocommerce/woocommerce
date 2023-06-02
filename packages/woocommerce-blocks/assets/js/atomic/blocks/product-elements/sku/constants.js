/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { barcode, Icon } from '@woocommerce/icons';

export const BLOCK_TITLE = __( 'Product SKU', 'woocommerce' );
export const BLOCK_ICON = <Icon srcElement={ barcode } />;
export const BLOCK_DESCRIPTION = __(
	'Display the SKU of a product.',
	'woocommerce'
);
