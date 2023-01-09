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
	initialAttributes: ProductAttribute[];
	productId?: number;
};

export type EnhancedProductAttribute = ProductAttribute & {
	terms?: ProductAttributeTerm[];
	visible?: boolean;
};

export function useProductAttributes( {
	initialAttributes = [],
	productId,
}: useProductAttributesProps ) {
	const [ attributes, setAttributes ] =
		useState< EnhancedProductAttribute[] >( initialAttributes );
	const [ localAttributes, globalAttributes ]: ProductAttribute[][] = sift(
		attributes,
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
		if ( ! initialAttributes.length || attributes.length ) {
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
	}, [ attributes, fetchTerms, initialAttributes ] );

	return {
		attributes,
		setAttributes,
	};
}
