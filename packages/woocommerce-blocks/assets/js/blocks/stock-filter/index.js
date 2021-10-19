/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { Icon, server } from '@woocommerce/icons';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import edit from './edit.js';

registerBlockType( 'woocommerce/stock-filter', {
	title: __( 'Filter Products by Stock', 'woocommerce' ),
	icon: {
		src: <Icon srcElement={ server } />,
		foreground: '#96588a',
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woocommerce' ) ],
	description: __(
		'Allow customers to filter the grid by products stock status. Works in combination with the All Products block.',
		'woocommerce'
	),
	supports: {
		html: false,
		multiple: false,
	},
	example: {
		attributes: {
			isPreview: true,
		},
	},
	attributes: {
		heading: {
			type: 'string',
			default: __(
				'Filter by stock status',
				'woocommerce'
			),
		},
		headingLevel: {
			type: 'number',
			default: 3,
		},
		showCounts: {
			type: 'boolean',
			default: true,
		},
		showFilterButton: {
			type: 'boolean',
			default: false,
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
	// Save the props to post content.
	save( { attributes } ) {
		const {
			className,
			showCounts,
			heading,
			headingLevel,
			showFilterButton,
		} = attributes;
		const data = {
			'data-show-counts': showCounts,
			'data-heading': heading,
			'data-heading-level': headingLevel,
		};
		if ( showFilterButton ) {
			data[ 'data-show-filter-button' ] = showFilterButton;
		}
		return (
			<div
				className={ classNames( 'is-loading', className ) }
				{ ...data }
			>
				<span
					aria-hidden
					className="wc-block-product-stock-filter__placeholder"
				/>
			</div>
		);
	},
} );
