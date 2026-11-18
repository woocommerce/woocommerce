/**
 * External dependencies
 */
import { getSetting } from '@woocommerce/settings';
import { AttributeObject, AttributeSetting } from '@woocommerce/types';

const ATTRIBUTES = getSetting< AttributeSetting[] >( 'attributes', [] );

/**
 * Format an attribute from the settings into an object with standardized keys.
 */
function attributeSettingToObject( attribute: AttributeSetting ) {
	if ( ! attribute || ! attribute.attribute_name ) {
		return null;
	}
	return {
		id: parseInt( attribute.attribute_id, 10 ),
		name: attribute.attribute_name,
		taxonomy: 'pa_' + attribute.attribute_name,
		label: attribute.attribute_label,
	};
}

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
 * Get attribute data by its taxonomy ID.
 */
export function getAttributeFromId( attributeId: number ) {
	if ( ! attributeId ) {
		return;
	}
	return attributeObjects.find( ( attribute ) => {
		return attribute.id === attributeId;
	} );
}

/**
 * Get attribute data by taxonomy name.
 */
export function getAttributeFromTaxonomy( taxonomy: string ) {
	if ( ! taxonomy ) {
		return;
	}
	return attributeObjects.find( ( attribute ) => {
		return attribute.taxonomy === taxonomy;
	} );
}

/**
 * Get the ID of the store's current default filter attribute, or 0 if none.
 */
export function getDefaultAttributeId(): number {
	const defaultAttribute = getSetting< AttributeSetting | undefined >(
		'defaultProductFilterAttribute',
		undefined
	);
	return parseInt( defaultAttribute?.attribute_id ?? '0', 10 ) || 0;
}
