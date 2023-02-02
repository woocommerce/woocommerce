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
	allAttributes: ProductAttribute[];
	isVariationAttributes?: boolean;
	onChange: ( attributes: ProductAttribute[] ) => void;
	productId?: number;
};

export type EnhancedProductAttribute = ProductAttribute & {
	terms?: ProductAttributeTerm[];
	visible?: boolean;
};

export function useProductAttributes( {
	allAttributes = [],
	isVariationAttributes = false,
	onChange,
	productId,
}: useProductAttributesProps ) {
	const getFilteredAttributes = () => {
		return isVariationAttributes
			? allAttributes.filter( ( attribute ) => !! attribute.variation )
			: allAttributes.filter( ( attribute ) => ! attribute.variation );
	};

	const [ attributes, setAttributes ] = useState<
		EnhancedProductAttribute[]
	>( getFilteredAttributes() );
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

	const getAugmentedAttributes = ( atts: ProductAttribute[] ) => {
		return atts.map( ( attribute, index ) => ( {
			...attribute,
			variation: isVariationAttributes,
			position: attributes.length + index,
		} ) );
	};

	const handleChange = ( newAttributes: ProductAttribute[] ) => {
		const augmentedAttributes = getAugmentedAttributes( newAttributes );
		const otherAttributes = isVariationAttributes
			? allAttributes.filter( ( attribute ) => ! attribute.variation )
			: allAttributes.filter( ( attribute ) => !! attribute.variation );
		setAttributes( augmentedAttributes );
		onChange( [ ...otherAttributes, ...augmentedAttributes ] );
	};

	useEffect( () => {
		if ( ! getFilteredAttributes().length || attributes.length ) {
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
	}, [ allAttributes, attributes, fetchTerms ] );

	return {
		attributes,
		handleChange,
		setAttributes,
	};
}
