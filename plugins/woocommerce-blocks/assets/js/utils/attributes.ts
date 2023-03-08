/**
 * External dependencies
 */
import { SearchListItem } from '@woocommerce/editor-components/search-list-control/types';
import { getSetting } from '@woocommerce/settings';
import {
	AttributeObject,
	AttributeSetting,
	AttributeTerm,
	AttributeWithTerms,
	isAttributeTerm,
} from '@woocommerce/types';

const ATTRIBUTES = getSetting< AttributeSetting[] >( 'attributes', [] );

/**
 * Format an attribute from the settings into an object with standardized keys.
 *
 * @param {Object} attribute The attribute object.
 */
const attributeSettingToObject = ( attribute: AttributeSetting ) => {
	if ( ! attribute || ! attribute.attribute_name ) {
		return null;
	}
	return {
		id: parseInt( attribute.attribute_id, 10 ),
		name: attribute.attribute_name,
		taxonomy: 'pa_' + attribute.attribute_name,
		label: attribute.attribute_label,
	};
};

/**
 * Format all attribute settings into objects.
 */
const attributeObjects = ATTRIBUTES.reduce(
	( acc: Partial< AttributeObject >[], current ) => {
		const attributeObject = attributeSettingToObject( current );

		if ( attributeObject && attributeObject.id ) {
			acc.push( attributeObject );
		}

		return acc;
	},
	[]
);

/**
 * Converts an Attribute object into a shape compatible with the `SearchListControl`
 */
export const convertAttributeObjectToSearchItem = (
	attribute: AttributeObject | AttributeTerm | AttributeWithTerms
): SearchListItem => {
	const { count, id, name, parent } = attribute;

	return {
		count,
		id,
		name,
		parent,
		breadcrumbs: [],
		children: [],
		value: isAttributeTerm( attribute ) ? attribute.attr_slug : '',
	};
};

/**
 * Get attribute data by taxonomy.
 *
 * @param {number} attributeId The attribute ID.
 * @return {Object|undefined} The attribute object if it exists.
 */
export const getAttributeFromID = ( attributeId: number ) => {
	if ( ! attributeId ) {
		return;
	}
	return attributeObjects.find( ( attribute ) => {
		return attribute.id === attributeId;
	} );
};

/**
 * Get attribute data by taxonomy.
 *
 * @param {string} taxonomy The attribute taxonomy name e.g. pa_color.
 * @return {Object|undefined} The attribute object if it exists.
 */
export const getAttributeFromTaxonomy = ( taxonomy: string ) => {
	if ( ! taxonomy ) {
		return;
	}
	return attributeObjects.find( ( attribute ) => {
		return attribute.taxonomy === taxonomy;
	} );
};

/**
 * Get the taxonomy of an attribute by Attribute ID.
 *
 * @param {number} attributeId The attribute ID.
 * @return {string} The taxonomy name.
 */
export const getTaxonomyFromAttributeId = ( attributeId: number ) => {
	if ( ! attributeId ) {
		return null;
	}
	const attribute = getAttributeFromID( attributeId );
	return attribute ? attribute.taxonomy : null;
};
