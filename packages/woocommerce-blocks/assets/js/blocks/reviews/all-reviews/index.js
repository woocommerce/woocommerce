/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { Icon, discussion } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import '../editor.scss';
import Editor from './edit';
import sharedAttributes from '../attributes';
import save from '../save.js';
import { example } from '../example';

/**
 * Register and run the "All Reviews" block.
 * This block lists all product reviews.
 */
registerBlockType( 'woocommerce/all-reviews', {
	title: __( 'All Reviews', 'woocommerce' ),
	icon: {
		src: <Icon srcElement={ discussion } />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __(
		'Show a list of all product reviews.',
		'woocommerce'
	),
	supports: {
		html: false,
	},
	example: {
		...example,
		attributes: {
			...example.attributes,
			showProductName: true,
		},
	},
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
	 *
	 * @param {Object} props Props to pass to block.
	 */
	edit( props ) {
		return <Editor { ...props } />;
	},

	/**
	 * Save the props to post content.
	 */
	save,
} );
