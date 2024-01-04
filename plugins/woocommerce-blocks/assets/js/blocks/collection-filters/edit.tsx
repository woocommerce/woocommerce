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
