/**
 * External dependencies
 */
import { useBlockProps, InnerBlocks } from '@wordpress/block-editor';
import { Template } from '@wordpress/blocks';
import { useSelect } from '@wordpress/data';
import { getSetting } from '@woocommerce/settings';
import type { AttributeSetting } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { EditProps, FilterType } from './types';
import { getAllowedBlocks } from './utils';
import Downgrade from './components/downgrade';
import Warning from './components/warning';
import './editor.scss';

const DISALLOWED_BLOCKS = [
	'woocommerce/filter-wrapper',
	'woocommerce/product-filters',
];

const ATTRIBUTES = getSetting< AttributeSetting[] >( 'attributes', [] );

const firstAttribute = ATTRIBUTES.find( Boolean );

const templates: Partial< Record< FilterType, Template[] > > = {
	'active-filters': [ [ 'woocommerce/product-filters-active', {} ] ],
	'price-filter': [ [ 'woocommerce/product-filters-price', {} ] ],
	'stock-filter': [ [ 'woocommerce/product-filters-stock-status', {} ] ],
	'rating-filter': [ [ 'woocommerce/product-filters-rating', {} ] ],
	'product-filters': [
		[ 'woocommerce/product-filters-active', {} ],
		[ 'woocommerce/product-filters-price', {} ],
		[ 'woocommerce/product-filters-stock-status', {} ],
		[ 'woocommerce/product-filters-rating', {} ],
	],
};

if ( firstAttribute ) {
	templates[ 'attribute-filter' ] = [
		[
			'woocommerce/product-filters-attribute',
			{ attributeId: parseInt( firstAttribute?.attribute_id, 10 ) },
		],
	];

	templates[ 'product-filters' ]?.push( [
		'woocommerce/product-filters-attribute',
		{ attributeId: parseInt( firstAttribute?.attribute_id, 10 ) },
	] );
}

const Edit = ( props: EditProps ) => {
	const allowedBlocks = getAllowedBlocks( DISALLOWED_BLOCKS );

	const template = templates[ props.attributes.filterType ];

	const blockProps = useBlockProps();

	const isNested = useSelect( ( select ) => {
		const { getBlockParentsByBlockName } = select( 'core/block-editor' );
		return !! getBlockParentsByBlockName(
			props.clientId,
			'woocommerce/product-collection'
		).length;
	} );

	return (
		<nav { ...blockProps }>
			{ ! isNested && <Warning /> }
			<Downgrade clientId={ props.clientId } />
			<InnerBlocks
				template={ template }
				allowedBlocks={ allowedBlocks }
			/>
		</nav>
	);
};

export default Edit;
