/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { Icon, toggle } from '@woocommerce/icons';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import edit from './edit.js';

registerBlockType( 'woocommerce/active-filters', {
	title: __( 'Active Product Filters', 'woocommerce' ),
	icon: {
		src: <Icon srcElement={ toggle } />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __(
		'Show the currently active product filters. Works in combination with the All Products and filters blocks.',
		'woocommerce'
	),
	supports: {
		html: false,
		multiple: false,
	},
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
			default: __( 'Active filters', 'woocommerce' ),
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
		const { className, displayStyle, heading, headingLevel } = attributes;
		const data = {
			'data-display-style': displayStyle,
			'data-heading': heading,
			'data-heading-level': headingLevel,
		};
		return (
			<div
				className={ classNames( 'is-loading', className ) }
				{ ...data }
			>
				<span
					aria-hidden
					className="wc-block-active-product-filters__placeholder"
				/>
			</div>
		);
	},
} );
