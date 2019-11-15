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

registerBlockType( 'woocommerce/attribute-filter', {
	title: __( 'Filter Products by Attribute', 'woo-gutenberg-products-block' ),
	icon: {
		src: <Gridicon icon="menus" />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Display a list of filters based on a chosen product attribute.',
		'woo-gutenberg-products-block'
	),
	supports: {},
	example: {
		attributes: {
			isPreview: true,
		},
	},
	attributes: {
		attributeId: {
			type: 'number',
			default: 0,
		},
		showCounts: {
			type: 'boolean',
			default: true,
		},
		queryType: {
			type: 'string',
			default: 'or',
		},
		heading: {
			type: 'string',
			default: __(
				'Filter by attribute',
				'woo-gutenberg-products-block'
			),
		},
		headingLevel: {
			type: 'number',
			default: 3,
		},
		/**
		 * Are we previewing?
		 */
		isPreview: {
			type: 'boolean',
			default: false,
		},
	},
	edit,
	/**
	 * Save the props to post content.
	 */
	save( { attributes } ) {
		const {
			showCounts,
			queryType,
			attributeId,
			heading,
			headingLevel,
		} = attributes;
		const data = {
			'data-attribute-id': attributeId,
			'data-show-counts': showCounts,
			'data-query-type': queryType,
			'data-heading': heading,
			'data-heading-level': headingLevel,
		};
		return (
			<div className="is-loading" { ...data }>
				<span
					aria-hidden
					className="wc-block-product-attribute-filter__placeholder"
				/>
			</div>
		);
	},
} );
