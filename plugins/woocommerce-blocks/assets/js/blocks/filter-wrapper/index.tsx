/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { createBlock, registerBlockType } from '@wordpress/blocks';
import type { BlockInstance } from '@wordpress/blocks';
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

const filterBlocksWidgets = [
	{
		widgetId: 'woocommerce_layered_nav_filters',
		name: 'active-filters',
		heading: __( 'Active filters', 'woocommerce' ),
	},
	{
		widgetId: 'woocommerce_price_filter',
		name: 'price-filter',
		heading: __( 'Filter by price', 'woocommerce' ),
	},
	{
		widgetId: 'woocommerce_layered_nav',
		name: 'attribute-filter',
		heading: __( 'Filter by attribute', 'woocommerce' ),
	},
	{
		widgetId: 'woocommerce_rating_filter',
		name: 'rating-filter',
		heading: __( 'Filter by rating', 'woocommerce' ),
	},
];

const getTransformAttributes = ( instance, filterType: string ) => {
	switch ( filterType ) {
		case 'attribute-filter':
			return {
				attributeId: 0,
				showCounts: true,
				queryType: instance?.raw?.query_type || 'or',
				heading: '',
				displayStyle: instance?.raw?.display_type || 'list',
				showFilterButton: false,
				selectType: instance?.raw?.select_type || 'multiple',
				isPreview: false,
			};
		case 'active-filters':
			return {
				displayStyle: 'list',
				heading: '',
			};
		case 'price-filter':
			return {
				heading: '',
				showInputFields: false,
				showFilterButton: true,
				inlineInput: false,
			};
		default:
			return {};
	}
};

const isFilterWidget = ( widgetId: string ) =>
	filterBlocksWidgets.some( ( item ) => item.widgetId === widgetId );

const getFilterBlockObject = ( widgetId: string ) => {
	const filterBlock = filterBlocksWidgets.find(
		( item ) => item.widgetId === widgetId
	);
	return filterBlock;
};

const transformFilterBlock = (
	filterType: string,
	attributes: Record< string, unknown >,
	title: string
) => {
	const filterWrapperInnerBlocks: BlockInstance[] = [
		createBlock( `woocommerce/${ filterType }`, attributes ),
	];

	filterWrapperInnerBlocks.unshift(
		createBlock( 'core/heading', {
			content: title,
			level: 3,
		} )
	);

	return createBlock(
		'woocommerce/filter-wrapper',
		{
			filterType,
		},
		filterWrapperInnerBlocks
	);
};

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
			name: 'active-filters',
			title: __( 'Active Filters', 'woocommerce' ),
			description: __(
				'Display the currently active filters.',
				'woocommerce'
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
			name: 'price-filter',
			title: __( 'Filter by Price', 'woocommerce' ),
			description: __(
				'Enable customers to filter the product grid by choosing a price range.',
				'woocommerce'
			),
			isActive: ( attributes ) =>
				attributes.filterType === 'price-filter',
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
			name: 'stock-filter',
			title: __( 'Filter by Stock', 'woocommerce' ),
			description: __(
				'Enable customers to filter the product grid by stock status.',
				'woocommerce'
			),
			isActive: ( attributes ) =>
				attributes.filterType === 'stock-filter',
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
			name: 'attribute-filter',
			title: __( 'Filter by Attribute', 'woocommerce' ),
			description: __(
				'Enable customers to filter the product grid by selecting one or more attributes, such as color.',
				'woocommerce'
			),
			isActive: ( attributes ) =>
				attributes.filterType === 'attribute-filter',
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
			name: 'rating-filter',
			title: __( 'Filter by Rating', 'woocommerce' ),
			description: __(
				'Enable customers to filter the product grid by rating.',
				'woocommerce'
			),
			isActive: ( attributes ) =>
				attributes.filterType === 'rating-filter',
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
	transforms: {
		from: [
			{
				type: 'block',
				blocks: [ 'core/legacy-widget' ],
				// We can't transform if raw instance isn't shown in the REST API.
				isMatch: ( { idBase, instance } ) =>
					isFilterWidget( idBase ) && !! instance?.raw,
				transform: ( { idBase, instance } ) => {
					const filterBlockObject = getFilterBlockObject( idBase );
					if ( ! filterBlockObject ) return null;
					return transformFilterBlock(
						filterBlockObject.name,
						getTransformAttributes(
							instance,
							filterBlockObject.name
						),
						instance?.raw?.title || filterBlockObject.heading
					);
				},
			},
		],
	},
} );
