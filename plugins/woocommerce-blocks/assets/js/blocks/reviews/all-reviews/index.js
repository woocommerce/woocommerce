/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import Gridicon from 'gridicons';

/**
 * Internal dependencies
 */
import '../editor.scss';
import Editor from './edit';
import sharedAttributes from '../attributes';
import save from '../save.js';

/**
 * Register and run the "All Reviews" block.
 */
registerBlockType( 'woocommerce/all-reviews', {
	title: __( 'All Reviews', 'woo-gutenberg-products-block' ),
	icon: {
		src: <Gridicon icon="list" />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Shows a list of all product reviews.',
		'woo-gutenberg-products-block'
	),
	attributes: {
		...sharedAttributes,
		/**
		* Show the product name.
		*/
		showProductName: {
			type: 'boolean',
			default: true,
		},
	},

	/**
	 * Renders and manages the block.
	 */
	edit( props ) {
		return <Editor { ...props } />;
	},

	/**
	 * Save the props to post content.
	 */
	save,
} );
