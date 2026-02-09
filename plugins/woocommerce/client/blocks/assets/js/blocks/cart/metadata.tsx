/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { BlockConfiguration } from '@wordpress/blocks';
import { cart } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';

export const metadata: BlockConfiguration = {
	title: __( 'Cart', 'woocommerce' ),
	apiVersion: 3,
	icon: {
		src: (
			<Icon
				icon={ cart }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __( 'Shopping cart.', 'woocommerce' ),
	supports: {
		align: [ 'wide' ],
		html: false,
		multiple: false,
	},
	example: {
		attributes: {
			isPreview: true,
		},
		viewportWidth: 800,
	},
};
