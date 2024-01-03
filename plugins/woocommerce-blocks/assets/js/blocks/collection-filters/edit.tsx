/**
 * External dependencies
 */
import { InnerBlocks } from '@wordpress/block-editor';
import { getSetting } from '@woocommerce/settings';
import type { AttributeSetting } from '@woocommerce/types';
import { Template } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { EditProps, FilterType } from './types';
import { getAllowedBlocks } from './utils';

const DISALLOWED_BLOCKS = [
	// don't allow nesting filter blocks
	'woocommerce/collection-active-filters',
	'woocommerce/collection-price-filter',
	'woocommerce/collection-stock-filter',
	'woocommerce/collection-rating-filter',
	'woocommerce/collection-attribute-filter',
	// don't allow nesting old filter blocks
	'woocommerce/filter-wrapper',
	// don't allow nesting variants
	'woocommerce/collection-filters-variant',
	'woocommerce/rating-filter-variant',
	'woocommerce/price-filter-variant',
	'woocommerce/active-filters-variant',
	'woocommerce/attribute-filter-variant',
	'woocommerce/stock-filter-variant',
];

const DISALLOWED_COLLECTION_FILTERS_BLOCKS = [
	'woocommerce/collection-filters',
	// don't allow nesting variants
	'woocommerce/collection-filters-variant',
	'woocommerce/rating-filter-variant',
	'woocommerce/price-filter-variant',
	'woocommerce/active-filters-variant',
	'woocommerce/attribute-filter-variant',
	'woocommerce/stock-filter-variant',
	// don't allow nesting old filter blocks
	'woocommerce/filter-wrapper',
];

const ATTRIBUTES = getSetting< AttributeSetting[] >( 'attributes', [] );

const firstAttribute = ATTRIBUTES.find( Boolean );

const templates: Partial< Record< FilterType, Template[] > > = {
	'active-filters': [ [ 'woocommerce/collection-active-filters', {} ] ],
	'price-filter': [ [ 'woocommerce/collection-price-filter', {} ] ],
	'stock-filter': [ [ 'woocommerce/collection-stock-filter', {} ] ],
	'rating-filter': [ [ 'woocommerce/collection-rating-filter', {} ] ],
	'collection-filters': [
		[ 'woocommerce/collection-active-filters', {} ],
		[ 'woocommerce/collection-price-filter', {} ],
		[ 'woocommerce/collection-stock-filter', {} ],
		[ 'woocommerce/collection-rating-filter', {} ],
		[ 'woocommerce/collection-attribute-filter', {} ],
	],
};

if ( firstAttribute ) {
	templates[ 'attribute-filter' ] = [
		[
			'woocommerce/collection-attribute-filter',
			{ attributeId: parseInt( firstAttribute?.attribute_id, 10 ) },
		],
	];
}

const Edit = ( props: EditProps ) => {
	const filterType = props.attributes.filterType;

	const allowedBlocks =
		filterType === 'collection-filters'
			? getAllowedBlocks( DISALLOWED_COLLECTION_FILTERS_BLOCKS )
			: getAllowedBlocks( DISALLOWED_BLOCKS );

	const template = templates[ props.attributes.filterType ];

	return (
		<nav>
			<InnerBlocks
				template={ template }
				allowedBlocks={ allowedBlocks }
			/>
		</nav>
	);
};

export default Edit;
