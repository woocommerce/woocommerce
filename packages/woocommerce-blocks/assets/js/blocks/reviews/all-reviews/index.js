/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import '../editor.scss';
import Editor from './edit';
import { IconAllReviews } from '../../../components/icons';
import sharedAttributes from '../attributes';
import save from '../save.js';

/**
 * Register and run the "All Reviews" block.
 */
registerBlockType( 'woocommerce/all-reviews', {
	title: __( 'All Reviews', 'woocommerce' ),
	icon: {
		src: <IconAllReviews fillColor="#96588a" />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __(
		'Shows a list of all product reviews.',
		'woocommerce'
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
