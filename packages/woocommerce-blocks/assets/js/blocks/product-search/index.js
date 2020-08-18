/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { Icon, search } from '@woocommerce/icons';
/**
 * Internal dependencies
 */
import './style.scss';
import './editor.scss';
import Block from './block.js';
import edit from './edit.js';

registerBlockType( 'woocommerce/product-search', {
	title: __( 'Product Search', 'woocommerce' ),
	icon: {
		src: <Icon srcElement={ search } />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __(
		'A search box to allow customers to search for products by keyword.',
		'woocommerce'
	),
	supports: {
		align: [ 'wide', 'full' ],
	},
	example: {
		attributes: {
			hasLabel: true,
		},
	},
	attributes: {
		/**
		 * Whether to show the field label.
		 */
		hasLabel: {
			type: 'boolean',
			default: true,
		},

		/**
		 * Search field label.
		 */
		label: {
			type: 'string',
			default: __( 'Search', 'woocommerce' ),
			source: 'text',
			selector: 'label',
		},

		/**
		 * Search field placeholder.
		 */
		placeholder: {
			type: 'string',
			default: __( 'Search products…', 'woocommerce' ),
			source: 'attribute',
			selector: 'input.wc-block-product-search__field',
			attribute: 'placeholder',
		},

		/**
		 * Store the instance ID.
		 */
		formId: {
			type: 'string',
			default: '',
		},
	},

	edit,

	/**
	 * Save the props to post content.
	 *
	 * @param {Object} attributes Props to pass to block.
	 */
	save( attributes ) {
		return (
			<div>
				<Block { ...attributes } />
			</div>
		);
	},
} );
