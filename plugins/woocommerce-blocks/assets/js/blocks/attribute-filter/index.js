/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { useBlockProps } from '@wordpress/block-editor';
import { isFeaturePluginBuild } from '@woocommerce/block-settings';
import { Icon, category } from '@wordpress/icons';
import classNames from 'classnames';

/**
 * Internal dependencies
 */
import edit from './edit.js';

registerBlockType( 'woocommerce/attribute-filter', {
	apiVersion: 2,
	title: __( 'Filter Products by Attribute', 'woo-gutenberg-products-block' ),
	icon: {
		src: (
			<Icon
				icon={ category }
				className="wc-block-editor-components-block-icon"
			/>
		),
	},
	category: 'woocommerce',
	keywords: [ __( 'WooCommerce', 'woo-gutenberg-products-block' ) ],
	description: __(
		'Allow customers to filter the grid by product attribute, such as color. Works in combination with the All Products block.',
		'woo-gutenberg-products-block'
	),
	supports: {
		html: false,
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
		displayStyle: {
			type: 'string',
			default: 'list',
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
			queryType,
			attributeId,
			heading,
			headingLevel,
			displayStyle,
			showFilterButton,
		} = attributes;
		const data = {
			'data-attribute-id': attributeId,
			'data-show-counts': showCounts,
			'data-query-type': queryType,
			'data-heading': heading,
			'data-heading-level': headingLevel,
		};
		if ( displayStyle !== 'list' ) {
			data[ 'data-display-style' ] = displayStyle;
		}
		if ( showFilterButton ) {
			data[ 'data-show-filter-button' ] = showFilterButton;
		}
		return (
			<div
				{ ...useBlockProps.save( {
					className: classNames( 'is-loading', className ),
				} ) }
				{ ...data }
			>
				<span
					aria-hidden
					className="wc-block-product-attribute-filter__placeholder"
				/>
			</div>
		);
	},
} );
