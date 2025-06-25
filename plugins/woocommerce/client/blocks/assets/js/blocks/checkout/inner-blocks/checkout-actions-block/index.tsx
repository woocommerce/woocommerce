/**
 * External dependencies
 */
import { Icon, button } from '@wordpress/icons';
import { registerBlockType } from '@wordpress/blocks';
import type { BlockConfiguration } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import attributes from './attributes';
import { Edit, Save } from './edit';
import metadata from './block.json';
import './style.scss';

const blockConfig: BlockConfiguration = {
	apiVersion: metadata.apiVersion,
	title: metadata.title,
	example: {
		attributes: {
			showPrice: true,
			placeOrderButtonLabel: __( 'Place Order', 'woocommerce' ),
			showReturnToCart: false,
		},
	},
	icon: {
		src: (
			<Icon
				icon={ button }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	attributes: { ...attributes, ...metadata.attributes },
	save: Save,
	edit: Edit,
};

registerBlockType( 'woocommerce/checkout-actions-block', blockConfig );
