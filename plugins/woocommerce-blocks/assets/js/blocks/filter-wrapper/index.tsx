/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { registerBlockType } from '@wordpress/blocks';
import { toggle } from '@woocommerce/icons';
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { Icon, category, currencyDollar, box } from '@wordpress/icons';

/**
 * Internal dependencies
 */
import edit from './edit';
import metadata from './block.json';

registerBlockType( metadata, {
	edit,
	save() {
		const innerBlocksProps = useInnerBlocksProps.save(
			useBlockProps.save()
		);
		return <div { ...innerBlocksProps } />;
	},
	variations: [
		{
			name: 'active-filters',
			title: __(
				'Active Product Filters',
				'woo-gutenberg-products-block'
			),
			description: __(
				'Display the currently active product filters.',
				'woo-gutenberg-products-block'
			),
			/**
			 * We need to handle the isActive function differently for this
			 * variation. The `attributes` is empty for default variation. So we
			 * set this variation as active if `filterType` is not passed.
			 */
			isActive: ( attributes ) =>
				attributes.filterType === 'active-filters' ||
				! attributes.filterType,
			attributes: {
				heading: __( 'Active filters', 'woo-gutenberg-products-block' ),
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
			name: 'price-filter',
			title: __( 'Filter by Price', 'woo-gutenberg-products-block' ),
			description: __(
				'Enable customers to filter the product grid by choosing a price range.',
				'woo-gutenberg-products-block'
			),
			isActive: ( attributes ) =>
				attributes.filterType === 'price-filter',
			attributes: {
				filterType: 'price-filter',
				heading: __(
					'Filter by price',
					'woo-gutenberg-products-block'
				),
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
			name: 'stock-filter',
			title: __( 'Filter by Stock', 'woo-gutenberg-products-block' ),
			description: __(
				'Enable customers to filter the product grid by stock status.',
				'woo-gutenberg-products-block'
			),
			isActive: ( attributes ) =>
				attributes.filterType === 'stock-filter',
			attributes: {
				filterType: 'stock-filter',
				heading: __(
					'Filter by stock status',
					'woo-gutenberg-products-block'
				),
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
			name: 'attribute-filter',
			title: __( 'Filter by Attribute', 'woo-gutenberg-products-block' ),
			description: __(
				'Enable customers to filter the product grid by selecting one or more attributes, such as color.',
				'woo-gutenberg-products-block'
			),
			isActive: ( attributes ) =>
				attributes.filterType === 'attribute-filter',
			attributes: {
				filterType: 'attribute-filter',
				heading: __(
					'Filter by attribute',
					'woo-gutenberg-products-block'
				),
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
	],
} );
