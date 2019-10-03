/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks } from '@wordpress/editor';
import { registerBlockType } from '@wordpress/blocks';
import { DEFAULT_HEIGHT } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import './style.scss';
import './editor.scss';
import Block from './block';
import { IconFolderStar } from '../../components/icons';

/**
 * Register and run the "Featured Category" block.
 */
registerBlockType( 'woocommerce/featured-category', {
	title: __( 'Featured Category', 'woocommerce' ),
	icon: {
		src: <IconFolderStar />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __(
		'Visually highlight a product category and encourage prompt action.',
		'woocommerce'
	),
	supports: {
		align: [ 'wide', 'full' ],
		html: false,
	},
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
		 * Text for the category link.
		 */
		linkText: {
			type: 'string',
			default: __( 'Shop now', 'woocommerce' ),
		},

		/**
		 * The category ID to display.
		 */
		categoryId: {
			type: 'number',
		},

		/**
		 * Show the category description.
		 */
		showDesc: {
			type: 'boolean',
			default: true,
		},
	},

	/**
	 * Renders and manages the block.
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
