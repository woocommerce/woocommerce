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
