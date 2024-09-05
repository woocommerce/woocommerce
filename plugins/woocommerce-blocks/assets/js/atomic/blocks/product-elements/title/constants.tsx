/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { heading, Icon } from '@wordpress/icons';

export const BLOCK_TITLE: string = __( 'Product Title', 'woocommerce' );
export const BLOCK_ICON: JSX.Element = (
	<Icon icon={ heading } className="wc-block-editor-components-block-icon" />
);
export const BLOCK_DESCRIPTION: string = __(
	'Display the title of a product.',
	'woocommerce'
);
