/**
 * External dependencies
 */
import {
	EXPERIMENTAL_PRODUCT_ATTRIBUTE_TERMS_STORE_NAME,
	Product,
	ProductAttribute,
	ProductAttributeTerm,
	ProductDefaultAttribute,
} from '@woocommerce/data';
import { resolveSelect } from '@wordpress/data';
import { useCallback, useEffect, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { sift } from '../utils';

export type EnhancedProductAttribute = ProductAttribute & {
	isDefault?: boolean;
	terms?: ProductAttributeTerm[];
	visible?: boolean;
};

type useProductAttributesProps = {
	allAttributes: ProductAttribute[];
	isVariationAttributes?: boolean;
	onChange: (
		attributes: ProductAttribute[],
		defaultAttributes: ProductDefaultAttribute[]
	) => void;
	productId?: number;
};

const getFilteredAttributes = (
	attr: ProductAttribute[],
	isVariationAttributes: boolean
) => {
	return isVariationAttributes
		? attr.filter( ( attribute ) => !! attribute.variation )
		: attr.filter( ( attribute ) => ! attribute.variation );
};

function manageDefaultAttributes( values: EnhancedProductAttribute[] ) {
	return values.reduce< Product[ 'default_attributes' ] >(
		( prevDefaultAttributes, currentAttribute ) => {
			if (
				// defaults to true.
				currentAttribute.isDefault === undefined ||
				currentAttribute.isDefault === true
			) {
				return [
					...prevDefaultAttributes,
					{
						id: currentAttribute.id,
						name: currentAttribute.name,
						option: currentAttribute.options[ 0 ],
					},
				];
			}
			return prevDefaultAttributes;
		},
		[]
	);
}

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
		allTerms: ProductAttributeTerm[]
	) => {
		return {
			...globalAttribute,
			terms: ( allTerms || [] ).filter( ( term ) =>
				globalAttribute.options.includes( term.name )
			),
		};
	};

	const getAugmentedAttributes = (
		atts: EnhancedProductAttribute[],
		variation: boolean,
		startPosition: number
	): ProductAttribute[] => {
		return atts.map( ( { isDefault, terms, ...attribute }, index ) => ( {
			...attribute,
			variation,
			position: startPosition + index,
		} ) );
	};

	const handleChange = ( newAttributes: EnhancedProductAttribute[] ) => {
		const defaultAttributes = manageDefaultAttributes( newAttributes );
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
			onChange(
				[ ...otherAugmentedAttributes, ...newAugmentedAttributes ],
				defaultAttributes
			);
		} else {
			onChange(
				[ ...newAugmentedAttributes, ...otherAugmentedAttributes ],
				defaultAttributes
			);
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
