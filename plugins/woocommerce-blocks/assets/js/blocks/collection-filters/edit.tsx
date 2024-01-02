/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { getSetting } from '@woocommerce/settings';
import type { AttributeSetting } from '@woocommerce/types';

/**
 * Internal dependencies
 */
import { EditProps, FilterType } from './types';

const ATTRIBUTES = getSetting< AttributeSetting[] >( 'attributes', [] );

const firstAttribute = ATTRIBUTES.find( Boolean );

const templates: Partial< Record< FilterType, [ string, unknown ] > > = {
	'active-filters': [ 'woocommerce/collection-active-filters', {} ],
	'price-filter': [ 'woocommerce/collection-price-filter', {} ],
	'stock-filter': [ 'woocommerce/collection-stock-filter', {} ],
	'rating-filter': [ 'woocommerce/collection-rating-filter', {} ],
};

if ( firstAttribute ) {
	templates[ 'attribute-filter' ] = [
		'woocommerce/collection-attribute-filter',
		{ attributeId: parseInt( firstAttribute?.attribute_id, 10 ) },
	];
}

const Edit = ( props: EditProps ) => {
	const template = [ templates[ props.attributes.filterType ] ];
	const blockProps = useBlockProps();
	const innerBlockProps = useInnerBlocksProps( blockProps, {
		template,
	} );

	return <nav { ...innerBlockProps } />;
};

export default Edit;
