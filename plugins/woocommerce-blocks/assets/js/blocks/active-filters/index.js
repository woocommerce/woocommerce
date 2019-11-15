/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import Gridicon from 'gridicons';

/**
 * Internal dependencies
 */
import edit from './edit.js';

registerBlockType( 'woocommerce/active-filters', {
	title: __( 'Active Product Filters', 'woo-gutenberg-products-block' ),
	icon: {
		src: <Gridicon icon="list-checkmark" />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Display a list of active product filters.',
		'woo-gutenberg-products-block'
	),
	supports: {},
	example: {
		attributes: {},
	},
	attributes: {
		displayStyle: {
			type: 'string',
			default: 'list',
		},
		heading: {
			type: 'string',
			default: __( 'Active filters', 'woo-gutenberg-products-block' ),
		},
		headingLevel: {
			type: 'number',
			default: 3,
		},
	},
	edit,
	/**
	 * Save the props to post content.
	 */
	save( { attributes } ) {
		const { displayStyle, heading, headingLevel } = attributes;
		const data = {
			'data-display-style': displayStyle,
			'data-heading': heading,
			'data-heading-level': headingLevel,
		};
		return (
			<div className="is-loading" { ...data }>
				<span
					aria-hidden
					className="wc-block-active-product-filters__placeholder"
				/>
			</div>
		);
	},
} );
