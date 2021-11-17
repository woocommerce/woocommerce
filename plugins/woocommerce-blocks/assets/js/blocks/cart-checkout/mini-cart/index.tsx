/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { Icon, cart } from '@woocommerce/icons';
import { registerExperimentalBlockType } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import edit from './edit';

const settings = {
	apiVersion: 2,
	title: __( 'Mini Cart', 'woo-gutenberg-products-block' ),
	icon: {
		src: <Icon srcElement={ cart } />,
		foreground: '#7f54b3',
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
		color: {
			/**
			 * Because we don't target the wrapper element, we don't need
			 * to add color classes and style to the wrapper.
			 */
			__experimentalSkipSerialization: true,
		},
		/**
		 * We need this experimental flag because we don't want to style the
		 * wrapper but inner elements.
		 */
		__experimentalSelector:
			'.wc-block-mini-cart__button, .wc-block-mini-cart__badge',
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
		transparentButton: {
			type: 'boolean',
			default: true,
		},
	},

	edit,

	save() {
		return null;
	},
};

registerExperimentalBlockType( 'woocommerce/mini-cart', settings );
