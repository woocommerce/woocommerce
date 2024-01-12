/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { toggle } from '@woocommerce/icons';
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import {
	Icon,
	category,
	currencyDollar,
	box,
	starEmpty,
} from '@wordpress/icons';

/**
 * Internal dependencies
 */
import edit from './edit';
import metadata from './block.json';

registerBlockType( metadata, {
	edit,
	save() {
		return (
			<div { ...useBlockProps.save() }>
				<InnerBlocks.Content />
			</div>
		);
	},
	variations: [
		{
			name: 'product-filters-active',
			title: __( 'Active Filters (2)', 'woocommerce' ),
			description: __(
				'Display the currently active filters.',
				'woocommerce'
			),
			attributes: {
				heading: __( 'Active filters', 'woocommerce' ),
				filterType: 'active-filters',
			},
			icon: {
				src: (
					<Icon
						icon={ toggle }
						className="wc-block-editor-components-block-icon"
					/>
				),
			},
			isDefault: true,
		},
		{
			name: 'product-filters-price',
			title: __( 'Filter by Price (2)', 'woocommerce' ),
			description: __(
				'Enable customers to filter the product grid by choosing a price range.',
				'woocommerce'
			),
			attributes: {
				filterType: 'price-filter',
				heading: __( 'Filter by price', 'woocommerce' ),
			},
			icon: {
				src: (
					<Icon
						icon={ currencyDollar }
						className="wc-block-editor-components-block-icon"
					/>
				),
			},
		},
		{
			name: 'product-filters-stock-status',
			title: __( 'Filter by Stock (2)', 'woocommerce' ),
			description: __(
				'Enable customers to filter the product grid by stock status.',
				'woocommerce'
			),
			attributes: {
				filterType: 'stock-filter',
				heading: __( 'Filter by stock status', 'woocommerce' ),
			},
			icon: {
				src: (
					<Icon
						icon={ box }
						className="wc-block-editor-components-block-icon"
					/>
				),
			},
		},
		{
			name: 'product-filters-attribute',
			title: __( 'Filter by Attribute (2)', 'woocommerce' ),
			description: __(
				'Enable customers to filter the product grid by selecting one or more attributes, such as color.',
				'woocommerce'
			),
			attributes: {
				filterType: 'attribute-filter',
				heading: __( 'Filter by attribute', 'woocommerce' ),
			},
			icon: {
				src: (
					<Icon
						icon={ category }
						className="wc-block-editor-components-block-icon"
					/>
				),
			},
		},
		{
			name: 'product-filters-rating',
			title: __( 'Filter by Rating (2)', 'woocommerce' ),
			description: __(
				'Enable customers to filter the product grid by rating.',
				'woocommerce'
			),
			attributes: {
				filterType: 'rating-filter',
				heading: __( 'Filter by rating', 'woocommerce' ),
			},
			icon: {
				src: (
					<Icon
						icon={ starEmpty }
						className="wc-block-editor-components-block-icon"
					/>
				),
			},
		},
	],
} );
