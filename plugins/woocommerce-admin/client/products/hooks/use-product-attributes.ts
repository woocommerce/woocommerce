/**
 * External dependencies
 */
import {
	EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME,
	ProductAttribute,
	ProductAttributeTerm,
} from '@woocommerce/data';
import { resolveSelect } from '@wordpress/data';
import { useCallback, useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { sift } from '../../utils';

type useProductAttributesProps = {
	filter?: ( attribute: EnhancedProductAttribute ) => boolean;
	inputValue: ProductAttribute[];
	productId?: number;
};

export type EnhancedProductAttribute = ProductAttribute & {
	terms?: ProductAttributeTerm[];
	visible?: boolean;
};

export function useProductAttributes( {
	filter = () => true,
	inputValue,
	productId,
}: useProductAttributesProps ) {
	const [ attributes, setAttributes ] = useState<
		EnhancedProductAttribute[]
	>( [] );
	const [ localAttributes, globalAttributes ]: ProductAttribute[][] = sift(
		inputValue,
		( attr: ProductAttribute ) => attr.id === 0
	);

	const fetchTerms = useCallback(
		( attributeId: number ) => {
			return resolveSelect(
				EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME
			)
				.getProductAttributeTerms< ProductAttributeTerm[] >( {
					attribute_id: attributeId,
					product: productId,
				} )
				.then(
					( attributeTerms ) => {
						return attributeTerms;
					},
					( error ) => {
						return error;
					}
				);
		},
		[ productId ]
	);

	const enhanceAttribute = (
		globalAttribute: ProductAttribute,
		terms: ProductAttributeTerm[]
	) => {
		return {
			...globalAttribute,
			terms: terms.length > 0 ? terms : undefined,
			options: terms.length === 0 ? globalAttribute.options : [],
		};
	};

	useEffect( () => {
		if ( ! inputValue || attributes.length !== 0 ) {
			return;
		}

		Promise.all(
			globalAttributes.map( ( attr ) => fetchTerms( attr.id ) )
		).then( ( termData ) => {
			setAttributes( [
				...globalAttributes.map( ( attr, index ) =>
					enhanceAttribute( attr, termData[ index ] )
				),
				...localAttributes,
			] );
		} );
	}, [ fetchTerms, attributes, inputValue ] );

	const getFilteredAttributes = () => {
		return attributes.filter( filter );
	};

	return {
		attributes: getFilteredAttributes(),
		setAttributes,
	};
}
