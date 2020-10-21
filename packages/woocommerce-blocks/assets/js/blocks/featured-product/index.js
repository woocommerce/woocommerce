/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { DEFAULT_HEIGHT } from '@woocommerce/block-settings';
import { Icon, star } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import './style.scss';
import './editor.scss';
import { example } from './example';
import Block from './block';

/**
 * Register and run the "Featured Product" block.
 */
registerBlockType( 'woocommerce/featured-product', {
	title: __( 'Featured Product', 'woocommerce' ),
	icon: {
		src: <Icon srcElement={ star } />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __(
		'Visually highlight a product or variation and encourage prompt action.',
		'woocommerce'
	),
	supports: {
		align: [ 'wide', 'full' ],
		html: false,
	},
	example,
	attributes: {
		/**
		 * Alignment of content inside block.
		 */
		contentAlign: {
			type: 'string',
			default: 'center',
		},

		/**
		 * Percentage opacity of overlay.
		 */
		dimRatio: {
			type: 'number',
			default: 50,
		},

		/**
		 * Toggle for edit mode in the block preview.
		 */
		editMode: {
			type: 'boolean',
			default: true,
		},

		/**
		 * Focus point for the background image
		 */
		focalPoint: {
			type: 'object',
		},

		/**
		 * A fixed height for the block.
		 */
		height: {
			type: 'number',
			default: DEFAULT_HEIGHT,
		},

		/**
		 * ID for a custom image, overriding the product's featured image.
		 */
		mediaId: {
			type: 'number',
			default: 0,
		},

		/**
		 * URL for a custom image, overriding the product's featured image.
		 */
		mediaSrc: {
			type: 'string',
			default: '',
		},

		/**
		 * The overlay color, from the color list.
		 */
		overlayColor: {
			type: 'string',
		},

		/**
		 * The overlay color, if a custom color value.
		 */
		customOverlayColor: {
			type: 'string',
		},

		/**
		 * Text for the product link.
		 */
		linkText: {
			type: 'string',
			default: __( 'Shop now', 'woocommerce' ),
		},

		/**
		 * The product ID to display.
		 */
		productId: {
			type: 'number',
		},

		/**
		 * Show the product description.
		 */
		showDesc: {
			type: 'boolean',
			default: true,
		},

		/**
		 * Show the product price.
		 */
		showPrice: {
			type: 'boolean',
			default: true,
		},

		/**
		 * Product preview.
		 */
		previewProduct: {
			type: 'object',
			default: null,
		},
	},

	/**
	 * Renders and manages the block.
	 *
	 * @param {Object} props Props to pass to block.
	 */
	edit( props ) {
		return <Block { ...props } />;
	},

	/**
	 * Block content is rendered in PHP, not via save function.
	 */
	save() {
		return <InnerBlocks.Content />;
	},
} );
