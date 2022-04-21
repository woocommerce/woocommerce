/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { InnerBlocks } from '@wordpress/block-editor';
import { registerBlockType } from '@wordpress/blocks';
import { getSetting } from '@woocommerce/settings';
import { folderStarred } from '@woocommerce/icons';
import { Icon } from '@wordpress/icons';
import { isFeaturePluginBuild } from '@woocommerce/block-settings';

/**
 * Internal dependencies
 */
import './style.scss';
import './editor.scss';
import { example } from './example';
import { Edit } from './edit';

/**
 * Register and run the "Featured Category" block.
 */
registerBlockType( 'woocommerce/featured-category', {
	apiVersion: 2,
	title: __( 'Featured Category', 'woo-gutenberg-products-block' ),
	icon: {
		src: (
			<Icon
				icon={ folderStarred }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Visually highlight a product category and encourage prompt action.',
		'woo-gutenberg-products-block'
	),
	supports: {
		align: [ 'wide', 'full' ],
		html: false,
		color: {
			background: false,
			text: true,
			...( isFeaturePluginBuild() && {
				__experimentalDuotone:
					'.wc-block-featured-category__background-image',
			} ),
		},
		spacing: {
			padding: true,
			...( isFeaturePluginBuild() && {
				__experimentalDefaultControls: {
					padding: true,
				},
				__experimentalSkipSerialization: true,
			} ),
		},
		...( isFeaturePluginBuild() && {
			__experimentalBorder: {
				color: true,
				radius: true,
				width: true,
				__experimentalSkipSerialization: true,
			},
		} ),
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
		 * Whether the image should fit the container or not be resized
		 *
		 * Note: when the image is resized to fit the container, the user loses
		 * the ability to have full control over the focus.
		 */
		imageFit: {
			type: 'string',
			default: 'none',
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
		 * A minimum height for the block.
		 *
		 * Note: if padding is increased, this way the inner content will never
		 * overflow, but instead will resize the container.
		 *
		 * It was decided to change this to make this block more in line with
		 * the “Cover” block.
		 */
		minHeight: {
			type: 'number',
			default: getSetting( 'default_height', 500 ),
		},

		/**
		 * Text for the category link.
		 */
		linkText: {
			type: 'string',
			default: __( 'Shop now', 'woo-gutenberg-products-block' ),
		},

		/**
		 * The category ID to display.
		 */
		categoryId: {
			type: 'number',
		},

		/**
		 * Color for the overlay layer on top of the product image.
		 */
		overlayColor: {
			type: 'string',
			default: '#000000',
		},

		/**
		 * Gradient for the overlay layer on top of the product image.
		 */
		overlayGradient: {
			type: 'string',
		},

		/**
		 * Category preview.
		 */
		previewCategory: {
			type: 'object',
			default: null,
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
	 *
	 * @param {Object} props Props to pass to block.
	 */
	edit: Edit,

	/**
	 * Block content is rendered in PHP, not via save function.
	 */
	save: () => {
		return <InnerBlocks.Content />;
	},
} );
