/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { Template, TemplateArray } from '@wordpress/blocks';
import { Field } from '@wordpress/dataviews';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { getProductField } from './fields';

/**
 * Get the property key for a field definition
 *
 * @param field - The field definition
 * @return The key for the field
 */
function getFieldKey( field: Template ): string {
	const attributes = field[ 1 ] || {};
	// We support the block binding structure.
	if (
		attributes.metadata?.bindings?.value?.source ===
			'woocommerce/entity-product' &&
		attributes.metadata?.bindings?.value?.args?.prop
	) {
		return attributes.metadata?.bindings?.value?.args?.prop;
	} else if ( attributes.property ) {
		return attributes.property;
	}
	return field[ 0 ];
}

/**
 * Hook that transforms field definitions into DataForm compatible field objects.
 * Each field definition is an array where:
 * - First item is the field name (matching a block definition)
 * - Second item is an object with field parameters
 *
 * @param fields - Array of field definitions
 * @return Array of DataForm compatible field objects
 */
export function useDataFormProductFields(
	fields: TemplateArray = []
): Field< Product >[] {
	return useMemo( () => {
		return fields.map( ( [ fieldName, params ] ) => {
			const getFieldDefinition = getProductField( fieldName );
			// Convert the field definition to a DataForm field format
			const field: Field< Product > = {
				...getFieldDefinition,
				id: getFieldKey( [ fieldName, params ] ),
			};

			return field;
		} );
	}, [ fields ] );
}
