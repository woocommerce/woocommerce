/**
 * External dependencies
 */
import { useBlockProps, useInnerBlocksProps } from '@wordpress/block-editor';
import { getSetting } from '@woocommerce/settings';
import type { AttributeSetting } from '@woocommerce/types';

const ATTRIBUTES = getSetting< AttributeSetting[] >( 'attributes', [] );

const firstAttribute = ATTRIBUTES.find( Boolean );

const template = [
	[ 'woocommerce/collection-active-filters', {} ],
	[ 'woocommerce/collection-price-filter', {} ],
	[ 'woocommerce/collection-stock-filter', {} ],
	[ 'woocommerce/collection-rating-filter', {} ],
];

if ( firstAttribute ) {
	template.push( [
		'woocommerce/collection-attribute-filter',
		{ attributeId: parseInt( firstAttribute?.attribute_id, 10 ) },
	] );
}

const Edit = () => {
	const blockProps = useBlockProps();
	const innerBlockProps = useInnerBlocksProps( blockProps, {
		template,
	} );

	return <nav { ...innerBlockProps } />;
};

export default Edit;
