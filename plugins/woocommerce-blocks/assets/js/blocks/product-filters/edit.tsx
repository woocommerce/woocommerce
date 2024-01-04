/**
 * External dependencies
 */
import { InnerBlocks, useBlockProps } from '@wordpress/block-editor';
import { getSetting } from '@woocommerce/settings';
import type { AttributeSetting } from '@woocommerce/types';
import { Template } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { EditProps, FilterType } from './types';
import { getAllowedBlocks } from './utils';

const DISALLOWED_BLOCKS = [
	'woocommerce/filter-wrapper',
	'woocommerce/collection-filters',
];

const ATTRIBUTES = getSetting< AttributeSetting[] >( 'attributes', [] );

const firstAttribute = ATTRIBUTES.find( Boolean );

const templates: Partial< Record< FilterType, Template[] > > = {
	'active-filters': [ [ 'woocommerce/product-filters-active', {} ] ],
	'price-filter': [ [ 'woocommerce/product-filters-price', {} ] ],
	'stock-filter': [ [ 'woocommerce/product-filters-stock', {} ] ],
	'rating-filter': [ [ 'woocommerce/product-filters-rating', {} ] ],
	'product-filters': [
		[ 'woocommerce/product-filters-active', {} ],
		[ 'woocommerce/product-filters-price', {} ],
		[ 'woocommerce/product-filters-stock-status', {} ],
		[ 'woocommerce/product-filters-rating', {} ],
		[ 'woocommerce/product-filters-attribute', {} ],
	],
};

if ( firstAttribute ) {
	templates[ 'attribute-filter' ] = [
		[
			'woocommerce/product-filters-attribute',
			{ attributeId: parseInt( firstAttribute?.attribute_id, 10 ) },
		],
	];
}

const Edit = ( props: EditProps ) => {
	const allowedBlocks = getAllowedBlocks( DISALLOWED_BLOCKS );

	const template = templates[ props.attributes.filterType ];

	const blockProps = useBlockProps();

	return (
		<nav { ...blockProps }>
			<InnerBlocks
				template={ template }
				allowedBlocks={ allowedBlocks }
			/>
		</nav>
	);
};

export default Edit;
