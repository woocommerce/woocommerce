/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { cart } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';
import { registerExperimentalBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import edit from './edit';

const settings = {
	apiVersion: 2,
	title: __( 'Mini Cart', 'woo-gutenberg-products-block' ),
	icon: {
		src: (
			<Icon
				icon={ cart }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Display a mini cart widget.',
		'woo-gutenberg-products-block'
	),
	supports: {
		html: false,
		multiple: false,
		color: true,
	},
	example: {
		attributes: {
			isPreview: true,
		},
	},
	attributes: {
		isPreview: {
			type: 'boolean',
			default: false,
			save: false,
		},
	},

	edit,

	save() {
		return null;
	},
};

registerExperimentalBlockType( 'woocommerce/mini-cart', settings );
