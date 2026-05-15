/**
 * External dependencies
 */
import {
	experimentalProductAttributeTermsStore,
	Product,
	type ProductProductAttribute,
	ProductAttributeTerm,
	ProductDefaultAttribute,
} from '@woocommerce/data';
import { resolveSelect } from '@wordpress/data';
import { useCallback, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { sift } from '../utils';

export type EnhancedProductAttribute = ProductProductAttribute & {
	isDefault?: boolean;
	terms?: ProductAttributeTerm[];
	visible?: boolean;
};

type useProductAttributesProps = {
	allAttributes: ProductProductAttribute[];
	isVariationAttributes?: boolean;
	/**
	 * When true and `isVariationAttributes` is false, attributes flagged for
	 * variations (`variation: true`) are also returned so they can be displayed
	 * as regular attributes for product types that do not support variations
	 * (e.g. grouped products). The underlying `variation` flag is preserved on
	 * the attribute data so switching back to a variation-capable product type
	 * keeps them as variation options.
	 */
	includeVariationAttributes?: boolean;
	onChange: (
		attributes: ProductProductAttribute[],
		defaultAttributes: ProductDefaultAttribute[]
	) => void;
	productId?: number;
};

const getFilteredAttributes = (
	attr: ProductProductAttribute[],
	isVariationAttributes: boolean,
	includeVariationAttributes = false
) => {
	if ( isVariationAttributes ) {
		return attr.filter( ( attribute ) => !! attribute.variation );
	}
	if ( includeVariationAttributes ) {
		return attr;
	}
	return attr.filter( ( attribute ) => ! attribute.variation );
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
	includeVariationAttributes = false,
	onChange,
	productId,
}: useProductAttributesProps ) {
	const [ attributes, setAttributes ] = useState<
		EnhancedProductAttribute[]
	>(
		getFilteredAttributes(
			allAttributes,
			isVariationAttributes,
			includeVariationAttributes
		)
	);

	const fetchTerms = useCallback(
		( attributeId: number ) => {
			return resolveSelect( experimentalProductAttributeTermsStore )
				.getProductAttributeTerms( {
					attribute_id: attributeId,
				} )
				.then(
					( attributeTerms ) => {
						return attributeTerms;
					},
					( error: string ) => {
						return error;
					}
				);
		},
		[ productId ]
	);

	const enhanceAttribute = (
		globalAttribute: ProductProductAttribute,
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
		startPosition: number,
		preserveVariation = false
	): ProductProductAttribute[] => {
		return atts.map( ( { isDefault, terms, ...attribute }, index ) => ( {
			...attribute,
			variation: preserveVariation ? !! attribute.variation : variation,
			position: startPosition + index,
		} ) );
	};

	const handleChange = ( newAttributes: EnhancedProductAttribute[] ) => {
		const defaultAttributes = manageDefaultAttributes( newAttributes );

		// When the non-variation block is rendering variation attributes
		// (e.g. grouped products) we keep each attribute's existing
		// `variation` flag instead of forcing it to false. This way the
		// underlying data is preserved across product-type switches.
		const preserveVariationFlag =
			! isVariationAttributes && includeVariationAttributes;

		let otherAttributes: ProductProductAttribute[];
		if ( isVariationAttributes ) {
			otherAttributes = allAttributes.filter(
				( attribute ) => ! attribute.variation
			);
		} else if ( includeVariationAttributes ) {
			// We're already rendering both variation and non-variation
			// attributes, so there are no "other" attributes to preserve.
			otherAttributes = [];
		} else {
			otherAttributes = allAttributes.filter(
				( attribute ) => !! attribute.variation
			);
		}

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
			isVariationAttributes ? otherAttributes.length : 0,
			preserveVariationFlag
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

	const fetchAttributes = useCallback( () => {
		const [
			localAttributes,
			globalAttributes,
		]: ProductProductAttribute[][] = sift(
			getFilteredAttributes(
				allAttributes,
				isVariationAttributes,
				includeVariationAttributes
			),
			( attr: ProductProductAttribute ) => attr.id === 0
		);

		Promise.all(
			globalAttributes.map( ( attr ) => fetchTerms( attr.id ) )
		).then( ( termData ) => {
			setAttributes( [
				...globalAttributes.map( ( attr, index ) =>
					// @ts-expect-error: termData definition is different from the expected type.
					enhanceAttribute( attr, termData[ index ] )
				),
				...localAttributes,
			] );
		} );
	}, [
		allAttributes,
		isVariationAttributes,
		includeVariationAttributes,
		fetchTerms,
	] );

	return {
		attributes,
		fetchAttributes,
		handleChange,
		setAttributes,
	};
}
