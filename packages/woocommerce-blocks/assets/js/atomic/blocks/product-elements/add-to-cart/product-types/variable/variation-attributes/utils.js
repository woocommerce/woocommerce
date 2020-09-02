/**
 * External dependencies
 */
import { keyBy } from 'lodash';

/**
 * Key an array of attributes by name,
 *
 * @param {Object} attributes Attributes array.
 */
export const getAttributes = ( attributes ) => {
	return attributes
		? keyBy(
				Object.values( attributes ).filter(
					( { has_variations: hasVariations } ) => hasVariations
				),
				'name'
		  )
		: [];
};

/**
 * Format variations from the API into a map of just the attribute names and values.
 *
 * @param {Array} variations Variations array.
 */
export const getVariationAttributes = ( variations ) => {
	if ( ! variations ) {
		return {};
	}

	const attributesMap = {};

	variations.forEach( ( { id, attributes } ) => {
		attributesMap[ id ] = attributes.reduce( ( acc, { name, value } ) => {
			acc[ name ] = value;
			return acc;
		}, [] );
	} );

	return attributesMap;
};

/**
 * Given a list of terms, filter them and return options for the select boxes.
 *
 * @param {Object} attributeTerms List of attribute term objects.
 * @param {?Array} validAttributeTerms Valid values if selections have been made already.
 * @return {Array} Value/Label pairs of select box options.
 */
export const getSelectControlOptions = (
	attributeTerms,
	validAttributeTerms = null
) => {
	return Object.values( attributeTerms )
		.map( ( { name, slug } ) => {
			if (
				validAttributeTerms === null ||
				validAttributeTerms.includes( null ) ||
				validAttributeTerms.includes( slug )
			) {
				return {
					value: slug,
					label: name,
				};
			}
			return null;
		} )
		.filter( Boolean );
};

/**
 * Given a list of variations and a list of attribute values, return variations which match.
 *
 * Allows an attribute to be excluded by name. This is used to filter displayed options for
 * individual attribute selects.
 *
 * @param {Object} props
 * @param {Object} props.selectedAttributes List of selected attributes.
 * @param {Object} props.variationAttributes List of variations and their attributes.
 * @param {Object} props.attributeNames List of all possible attribute names.
 * @return {Array} List of matching variation IDs.
 */
export const getVariationsMatchingSelectedAttributes = ( {
	selectedAttributes,
	variationAttributes,
	attributeNames,
} ) => {
	return Object.keys( variationAttributes ).filter( ( variationId ) =>
		attributeNames.every( ( attributeName ) => {
			const selectedAttribute = selectedAttributes[ attributeName ] || '';
			const variationAttribute =
				variationAttributes[ variationId ][ attributeName ];

			// If there is no selected attribute, consider this a match.
			if ( selectedAttribute === '' ) {
				return true;
			}
			// If the variation attributes for this attribute are set to null, it matches all values.
			if ( variationAttribute === null ) {
				return true;
			}
			// Otherwise, only match if the selected values are the same.
			return variationAttribute === selectedAttribute;
		} )
	);
};
