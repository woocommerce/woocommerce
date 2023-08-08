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
import { sift } from '../utils';

type useProductAttributesProps = {
	allAttributes: ProductAttribute[];
	isVariationAttributes?: boolean;
	onChange: ( attributes: ProductAttribute[] ) => void;
	productId?: number;
};

export type EnhancedProductAttribute = ProductAttribute & {
	terms?: ProductAttributeTerm[];
	visible?: boolean;
};

const getFilteredAttributes = (
	attr: ProductAttribute[],
	isVariationAttributes: boolean
) => {
	return isVariationAttributes
		? attr.filter( ( attribute ) => !! attribute.variation )
		: attr.filter( ( attribute ) => ! attribute.variation );
};

export function useProductAttributes( {
	allAttributes = [],
	isVariationAttributes = false,
	onChange,
	productId,
}: useProductAttributesProps ) {
	const [ attributes, setAttributes ] = useState<
		EnhancedProductAttribute[]
	>( getFilteredAttributes( allAttributes, isVariationAttributes ) );

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

	const getAugmentedAttributes = (
		atts: ProductAttribute[],
		variation: boolean,
		startPosition: number
	) => {
		return atts.map( ( attribute, index ) => ( {
			...attribute,
			variation,
			position: startPosition + index,
		} ) );
	};

	const handleChange = ( newAttributes: ProductAttribute[] ) => {
		let otherAttributes = isVariationAttributes
			? allAttributes.filter( ( attribute ) => ! attribute.variation )
			: allAttributes.filter( ( attribute ) => !! attribute.variation );

		// Remove duplicate global attributes.
		otherAttributes = otherAttributes.filter( ( attr ) => {
			if (
				attr.id > 0 &&
				newAttributes.some( ( a ) => a.id === attr.id )
			) {
				return false;
			}
			// Local attributes we check by name.
			if (
				attr.id === 0 &&
				newAttributes.some(
					( a ) => a.name.toLowerCase() === attr.name.toLowerCase()
				)
			) {
				return false;
			}
			return true;
		} );
		const newAugmentedAttributes = getAugmentedAttributes(
			newAttributes,
			isVariationAttributes,
			isVariationAttributes ? otherAttributes.length : 0
		);
		const otherAugmentedAttributes = getAugmentedAttributes(
			otherAttributes,
			! isVariationAttributes,
			isVariationAttributes ? 0 : newAttributes.length
		);

		if ( isVariationAttributes ) {
			onChange( [
				...otherAugmentedAttributes,
				...newAugmentedAttributes,
			] );
		} else {
			onChange( [
				...newAugmentedAttributes,
				...otherAugmentedAttributes,
			] );
		}
	};

	useEffect( () => {
		const [ localAttributes, globalAttributes ]: ProductAttribute[][] =
			sift(
				getFilteredAttributes( allAttributes, isVariationAttributes ),
				( attr: ProductAttribute ) => attr.id === 0
			);

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
	}, [ allAttributes, isVariationAttributes, fetchTerms ] );

	return {
		attributes,
		handleChange,
		setAttributes,
	};
}
