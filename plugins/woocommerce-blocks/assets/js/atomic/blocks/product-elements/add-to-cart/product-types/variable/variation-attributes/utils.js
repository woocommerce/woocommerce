/**
 * External dependencies
 */
import { keyBy } from 'lodash';
import { decodeEntities } from '@wordpress/html-entities';
import { isObject } from '@woocommerce/types';

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
		: {};
};

/**
 * Format variations from the API into a map of just the attribute names and values.
 *
 * Note, each item is keyed by the variation ID with an id: prefix. This is to prevent the object
 * being reordered when iterated.
 *
 * @param {Object} variations List of Variation objects and attributes keyed by variation ID.
 */
export const getVariationAttributes = ( variations ) => {
	if ( ! variations ) {
		return {};
	}

	const attributesMap = {};

	variations.forEach( ( { id, attributes } ) => {
		attributesMap[ `id:${ id }` ] = {
			id,
			attributes: attributes.reduce( ( acc, { name, value } ) => {
				acc[ name ] = value;
				return acc;
			}, {} ),
		};
	} );

	return attributesMap;
};

/**
 * Given a list of variations and a list of attribute values, return variations which match.
 *
 * Allows an attribute to be excluded by name. This is used to filter displayed options for
 * individual attribute selects.
 *
 * @param {Object} attributes          List of attribute names and terms.
 * @param {Object} variationAttributes Attributes for each variation keyed by variation ID.
 * @param {Object} selectedAttributes  Attribute Name Value pairs of current selections by the user.
 * @return {Array} List of matching variation IDs.
 */
export const getVariationsMatchingSelectedAttributes = (
	attributes,
	variationAttributes,
	selectedAttributes
) => {
	const variationIds = Object.values( variationAttributes ).map(
		( { id: variationId } ) => {
			return variationId;
		}
	);

	// If nothing is selected yet, just return all variations.
	if (
		Object.values( selectedAttributes ).every( ( value ) => value === '' )
	) {
		return variationIds;
	}

	const attributeNames = Object.keys( attributes );

	return variationIds.filter( ( variationId ) =>
		attributeNames.every( ( attributeName ) => {
			const selectedAttribute = selectedAttributes[ attributeName ] || '';
			const variationAttribute =
				variationAttributes[ 'id:' + variationId ].attributes[
					attributeName
				];

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

/**
 * Given a list of variations and a list of attribute values, returns the first matched variation ID.
 *
 * @param {Object} attributes          List of attribute names and terms.
 * @param {Object} variationAttributes Attributes for each variation keyed by variation ID.
 * @param {Object} selectedAttributes  Attribute Name Value pairs of current selections by the user.
 * @return {number} Variation ID.
 */
export const getVariationMatchingSelectedAttributes = (
	attributes,
	variationAttributes,
	selectedAttributes
) => {
	const matchingVariationIds = getVariationsMatchingSelectedAttributes(
		attributes,
		variationAttributes,
		selectedAttributes
	);
	return matchingVariationIds[ 0 ] || 0;
};

/**
 * Given a list of terms, filter them and return valid options for the select boxes.
 *
 * @see getActiveSelectControlOptions
 * @param {Object} attributeTerms      List of attribute term objects.
 * @param {?Array} validAttributeTerms Valid values if selections have been made already.
 * @return {Array} Value/Label pairs of select box options.
 */
const getValidSelectControlOptions = (
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
					label: decodeEntities( name ),
				};
			}
			return null;
		} )
		.filter( Boolean );
};

/**
 * Given a list of terms, filter them and return active options for the select boxes. This factors in
 * which options should be hidden due to current selections.
 *
 * @param {Object} attributes          List of attribute names and terms.
 * @param {Object} variationAttributes Attributes for each variation keyed by variation ID.
 * @param {Object} selectedAttributes  Attribute Name Value pairs of current selections by the user.
 * @return {Object} Select box options.
 */
export const getActiveSelectControlOptions = (
	attributes,
	variationAttributes,
	selectedAttributes
) => {
	const options = {};
	const attributeNames = Object.keys( attributes );
	const hasSelectedAttributes =
		Object.values( selectedAttributes ).filter( Boolean ).length > 0;

	attributeNames.forEach( ( attributeName ) => {
		const currentAttribute = attributes[ attributeName ];
		const selectedAttributesExcludingCurrentAttribute = {
			...selectedAttributes,
			[ attributeName ]: null,
		};
		// This finds matching variations for selected attributes apart from this one. This will be
		// used to get valid attribute terms of the current attribute narrowed down by those matching
		// variation IDs. For example, if I had Large Blue Shirts and Medium Red Shirts, I want to only
		// show Red shirts if Medium is selected.
		const matchingVariationIds = hasSelectedAttributes
			? getVariationsMatchingSelectedAttributes(
					attributes,
					variationAttributes,
					selectedAttributesExcludingCurrentAttribute
			  )
			: null;
		// Uses the above matching variation IDs to get the attributes from just those variations.
		const validAttributeTerms =
			matchingVariationIds !== null
				? matchingVariationIds.map(
						( varId ) =>
							variationAttributes[ 'id:' + varId ].attributes[
								attributeName
							]
				  )
				: null;
		// Intersects attributes with valid attributes.
		options[ attributeName ] = getValidSelectControlOptions(
			currentAttribute.terms,
			validAttributeTerms
		);
	} );

	return options;
};

/**
 * Return the default values of the given attributes in a format ready to be set in state.
 *
 * @param {Object} attributes List of attribute names and terms.
 * @return {Object} Default attributes.
 */
export const getDefaultAttributes = ( attributes = {} ) => {
	if ( ! isObject( attributes ) ) {
		return {};
	}

	const attributeNames = Object.keys( attributes );
	const defaultsToSet = {};

	if ( attributeNames.length === 0 ) {
		return defaultsToSet;
	}

	attributeNames.forEach( ( attributeName ) => {
		const currentAttribute = attributes[ attributeName ];
		const defaultValue = currentAttribute.terms.filter(
			( term ) => term.default
		);
		if ( defaultValue.length > 0 ) {
			defaultsToSet[ currentAttribute.name ] = defaultValue[ 0 ]?.slug;
		}
	} );

	return defaultsToSet;
};
