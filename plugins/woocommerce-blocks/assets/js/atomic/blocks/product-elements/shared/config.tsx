/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, grid } from '@wordpress/icons';
import type { BlockConfiguration } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import save from '../save';

/**
 * Holds default config for this collection of blocks.
 * attributes and title are omitted here as these are added on an individual block level.
 */
const sharedConfig: Omit< BlockConfiguration, 'attributes' | 'title' > = {
	category: 'woocommerce-product-elements',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	icon: {
		src: (
			<Icon
				icon={ grid }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	apiVersion: 3,
	supports: {
		html: false,
	},
	ancestor: [
		'woocommerce/all-products',
		'woocommerce/single-product',
		// Product Collection product template
		'woocommerce/product-template',
		// Products (Beta) product template
		'core/post-template',
	],
	save,
	deprecated: [
		{
			attributes: {},
			save(): null {
				return null;
			},
		},
	],
};

export default sharedConfig;
