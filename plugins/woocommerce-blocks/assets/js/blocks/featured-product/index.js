/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import Block from './block';
import { IconStar } from '../../components/icons';

/**
 * Register and run the "Featured Product" block.
 */
registerBlockType( 'woocommerce/featured-product', {
	title: __( 'Featured Product', 'woo-gutenberg-products-block' ),
	icon: <IconStar />,
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Visually highlight a product and encourage prompt action.',
		'woo-gutenberg-products-block'
	),
	supports: {
		align: [ 'wide', 'full' ],
	},
	attributes: {
		/**
		 * Toggle for edit mode in the block preview.
		 */
		editMode: {
			type: 'boolean',
			default: true,
		},

		/**
		 * The product ID to display
		 */
		productId: {
			type: 'number',
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
		return null;
	},
} );
