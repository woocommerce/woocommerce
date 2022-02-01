/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import classNames from 'classnames';
import { Icon, currencyDollar } from '@wordpress/icons';
import { isFeaturePluginBuild } from '@woocommerce/block-settings';
import { useBlockProps } from '@wordpress/block-editor';

/**
 * Internal dependencies
 */
import edit from './edit.js';

registerBlockType( 'woocommerce/price-filter', {
	apiVersion: 2,
	title: __( 'Filter Products by Price', 'woo-gutenberg-products-block' ),
	icon: {
		src: (
			<Icon
				icon={ currencyDollar }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Allow customers to filter the products by choosing a lower or upper price limit. Works in combination with the All Products block.',
		'woo-gutenberg-products-block'
	),
	supports: {
		html: false,
		multiple: false,
		color: {
			text: true,
			background: false,
		},
		...( isFeaturePluginBuild() && {
			__experimentalBorder: {
				radius: true,
				color: true,
				width: false,
			},
		} ),
	},
	example: {},
	attributes: {
		showInputFields: {
			type: 'boolean',
			default: true,
		},
		showFilterButton: {
			type: 'boolean',
			default: false,
		},
		heading: {
			type: 'string',
			default: __( 'Filter by price', 'woo-gutenberg-products-block' ),
		},
		headingLevel: {
			type: 'number',
			default: 3,
		},
	},

	edit,

	// Save the props to post content.
	save( { attributes } ) {
		const {
			className,
			showInputFields,
			showFilterButton,
			heading,
			headingLevel,
		} = attributes;
		const data = {
			'data-showinputfields': showInputFields,
			'data-showfilterbutton': showFilterButton,
			'data-heading': heading,
			'data-heading-level': headingLevel,
		};
		return (
			<div
				{ ...useBlockProps.save( {
					className: classNames( 'is-loading', className ),
				} ) }
				{ ...data }
			>
				<span
					aria-hidden
					className="wc-block-product-categories__placeholder"
				/>
			</div>
		);
	},
} );
