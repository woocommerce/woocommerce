/**
 * External dependencies
 */
import { registerBlockType } from '@wordpress/blocks';
import {
	Icon,
	box,
	category,
	currencyDollar,
	more,
	starEmpty,
} from '@wordpress/icons';
import { isExperimentalBuild } from '@woocommerce/block-settings';
import { __ } from '@wordpress/i18n';
import { toggle } from '@woocommerce/icons';

/**
 * Internal dependencies
 */
import metadata from './block.json';
import edit from './edit';
import save from './save';

if ( isExperimentalBuild() ) {
	registerBlockType( metadata, {
		icon: {
			src: (
				<Icon
					icon={ more }
					className="wc-block-editor-components-block-icon"
				/>
			),
		},
		edit,
		save,
		variations: [
			{
				name: 'product-filters-active',
				title: __( 'Product Filters: Active Filters', 'woocommerce' ),
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
				title: __( 'Product Filters: Price', 'woocommerce' ),
				description: __(
					'Enable customers to filter the product grid by choosing a price range.',
					'woocommerce'
				),
				attributes: {
					filterType: 'price-filter',
					heading: __( 'Filter by Price', 'woocommerce' ),
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
				title: __( 'Product Filters: Stock Status', 'woocommerce' ),
				description: __(
					'Enable customers to filter the product grid by stock status.',
					'woocommerce'
				),
				attributes: {
					filterType: 'stock-filter',
					heading: __( 'Filter by Stock Status', 'woocommerce' ),
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
				title: __( 'Product Filters: Attribute', 'woocommerce' ),
				description: __(
					'Enable customers to filter the product grid by selecting one or more attributes, such as color.',
					'woocommerce'
				),
				attributes: {
					filterType: 'attribute-filter',
					heading: __( 'Filter by Attribute', 'woocommerce' ),
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
				title: __( 'Product Filters: Rating', 'woocommerce' ),
				description: __(
					'Enable customers to filter the product grid by rating.',
					'woocommerce'
				),
				attributes: {
					filterType: 'rating-filter',
					heading: __( 'Filter by Rating', 'woocommerce' ),
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
}
