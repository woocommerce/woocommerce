/**
 * External dependencies
 */
import {
	BlockInstance,
	createBlock,
	registerBlockType,
} from '@wordpress/blocks';
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
import { BLOCK_NAME_MAP } from './constants';
import { BlockAttributes } from './types';

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
				name: 'product-filter-active',
				title: __(
					'Product Filter: Active Filters (Beta)',
					'woocommerce'
				),
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
				name: 'product-filter-price',
				title: __( 'Product Filter: Price (Beta)', 'woocommerce' ),
				description: __(
					'Enable customers to filter the product collection by choosing a price range.',
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
				name: 'product-filter-stock-status',
				title: __(
					'Product Filter: Stock Status (Beta)',
					'woocommerce'
				),
				description: __(
					'Enable customers to filter the product collection by stock status.',
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
				name: 'product-filter-attribute',
				title: __( 'Product Filter: Attribute (Beta)', 'woocommerce' ),
				description: __(
					'Enable customers to filter the product collection by selecting one or more attributes, such as color.',
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
				name: 'product-filter-rating',
				title: __( 'Product Filter: Rating (Beta)', 'woocommerce' ),
				description: __(
					'Enable customers to filter the product collection by rating.',
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
		transforms: {
			from: [
				{
					type: 'block',
					blocks: [ 'woocommerce/filter-wrapper' ],
					transform: (
						attributes: BlockAttributes,
						innerBlocks: BlockInstance[]
					) => {
						const newInnerBlocks: BlockInstance[] = [];
						// Loop through inner blocks to preserve the block order.
						innerBlocks.forEach( ( block ) => {
							if (
								block.name ===
								`woocommerce/${ attributes.filterType }`
							) {
								newInnerBlocks.push(
									createBlock(
										BLOCK_NAME_MAP[ attributes.filterType ],
										block.attributes
									)
								);
							}

							if ( block.name === 'core/heading' ) {
								newInnerBlocks.push( block );
							}
						} );

						return createBlock(
							'woocommerce/product-filter',
							attributes,
							newInnerBlocks
						);
					},
				},
			],
		},
	} );
}
