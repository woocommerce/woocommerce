/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { IconReviewsByProduct } from '@woocommerce/block-components/icons';

/**
 * Internal dependencies
 */
import '../editor.scss';
import Editor from './edit';
import sharedAttributes from '../attributes';
import save from '../save.js';
import { example } from '../example';

/**
 * Register and run the "Reviews by Product" block.
 */
registerBlockType( 'woocommerce/reviews-by-product', {
	title: __( 'Reviews by Product', 'woocommerce' ),
	icon: {
		src: <IconReviewsByProduct />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __(
		'Show reviews of your product to build trust.',
		'woocommerce'
	),
	example: {
		...example,
		attributes: {
			...example.attributes,
			productId: 1,
		},
	},
	attributes: {
		...sharedAttributes,
		/**
		 * The id of the product to load reviews for.
		 */
		productId: {
			type: 'number',
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
