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
		variations: [
			{
				name: 'woocommerce/collection-filters-variant',
				title: __( 'Collection Filters', 'woocommerce' ),
				description: __(
					'Enable customers to filter the product collection.',
					'woocommerce'
				),
				attributes: {
					filterType: 'collection-filters',
				},
				icon: {
					src: (
						<Icon
							icon={ more }
							className="wc-block-editor-components-block-icon"
						/>
					),
				},
				isDefault: true,
			},
			{
				name: 'woocommerce/active-filters-variant',
				title: __( 'Active Product Filters', 'woocommerce' ),
				description: __(
					'Display the currently active filters.',
					'woocommerce'
				),
				attributes: {
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
			},
			{
				name: 'woocommerce/price-filter-variant',
				title: __( 'Filter Products by Price', 'woocommerce' ),
				description: __(
					'Enable customers to filter the product collection by choosing a price range.',
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
				name: 'woocommerce/stock-filter-variant',
				title: __( 'Filter Products by Stock', 'woocommerce' ),
				description: __(
					'Enable customers to filter the product collection by stock status.',
					'woocommerce'
				),
				attributes: {
					filterType: 'stock-filter',
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
				name: 'woocommerce/attribute-filter-variant',
				title: __( 'Filter Products by Attribute', 'woocommerce' ),
				description: __(
					'Enable customers to filter the product grid by selecting one or more attributes, such as color.',
					'woocommerce'
				),
				attributes: {
					filterType: 'attribute-filter',
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
				name: 'woocommerce/rating-filter-variant',
				title: __( 'Filter Products by Rating', 'woocommerce' ),
				description: __(
					'Enable customers to filter the product collection by rating.',
					'woocommerce'
				),
				attributes: {
					filterType: 'rating-filter',
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
		edit,
		save,
	} );
}
