/**
 * External dependencies
 */
import {
	InnerBlocks,
	useBlockProps,
	useInnerBlocksProps,
} from '@wordpress/block-editor';
import { BlockEditProps, InnerBlockTemplate } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useCollection } from '@woocommerce/base-context/hooks';
import { AttributeTerm } from '@woocommerce/types';
import { Spinner } from '@wordpress/components';

/**
 * Internal dependencies
 */
import type { ProductFiltersBlockAttributes } from './types';

const TEMPLATE: InnerBlockTemplate[] = [
	[
		'core/heading',
		{
			level: 3,
			style: { typography: { fontSize: '24px' } },
			content: __( 'Filters', 'woocommerce' ),
		},
	],
	[
		'woocommerce/product-filter',
		{
			filterType: 'active-filters',
			heading: __( 'Active', 'woocommerce' ),
		},
	],
	[
		'woocommerce/product-filter',
		{
			filterType: 'price-filter',
			heading: __( 'Price', 'woocommerce' ),
		},
	],
	[
		'woocommerce/product-filter',
		{
			filterType: 'stock-filter',
			heading: __( 'Status', 'woocommerce' ),
		},
	],
	[
		'woocommerce/product-filter',
		{
			filterType: 'attribute-filter',
			heading: __( 'Attribute', 'woocommerce' ),
			attributeId: 0,
		},
	],
	[
		'woocommerce/product-filter',
		{
			filterType: 'rating-filter',
			heading: __( 'Rating', 'woocommerce' ),
		},
	],
];

const addHighestProductCountAttributeToTemplate = (
	template: InnerBlockTemplate[],
	highestProductCountAttribute: AttributeTerm | null
): InnerBlockTemplate[] => {
	if ( highestProductCountAttribute === null ) return template;

	return template.map( ( block ) => {
		const blockNameIndex = 0;
		const blockAttributesIndex = 1;
		const blockName = block[ blockNameIndex ];
		const blockAttributes = block[ blockAttributesIndex ];
		if (
			blockName === 'woocommerce/product-filter' &&
			blockAttributes?.filterType === 'attribute-filter'
		) {
			return [
				blockName,
				{
					...blockAttributes,
					heading: highestProductCountAttribute.name,
					attributeId: highestProductCountAttribute.id,
				},
			];
		}

		return block;
	} );
};

export const Edit = ( {}: BlockEditProps< ProductFiltersBlockAttributes > ) => {
	const blockProps = useBlockProps();
	const { results: attributes, isLoading } = useCollection< AttributeTerm >( {
		namespace: '/wc/store/v1',
		resourceName: 'products/attributes',
	} );

	const highestProductCountAttribute =
		attributes.reduce< AttributeTerm | null >(
			( attributeWithMostProducts, attribute ) => {
				if ( attributeWithMostProducts === null ) {
					return attribute;
				}
				return attribute.count > attributeWithMostProducts.count
					? attribute
					: attributeWithMostProducts;
			},
			null
		);
	const updatedTemplate = addHighestProductCountAttributeToTemplate(
		TEMPLATE,
		highestProductCountAttribute
	);

	if ( isLoading ) {
		return <Spinner />;
	}

	return (
		<div { ...blockProps }>
			<InnerBlocks templateLock={ false } template={ updatedTemplate } />
		</div>
	);
};

export const Save = () => {
	const blockProps = useBlockProps.save();
	const innerBlocksProps = useInnerBlocksProps.save( blockProps );
	return <div { ...innerBlocksProps } />;
};
