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
	} else if ( attributes.name ) {
		return attributes.name;
	}
	return field[ 0 ];
}

type FieldGroup = {
	type: 'fields';
	content: Field< Product >[];
};

type ColumnGroup = {
	type: 'column';
	content: Template;
};

/**
 * Hook that transforms field definitions into DataForm compatible field objects,
 * grouping fields that appear before and after column blocks.
 *
 * @param fields - Array of field definitions
 * @return Array of grouped fields and columns
 */
export function useDataFormProductFields(
	fields: TemplateArray = []
): ( FieldGroup | ColumnGroup )[] {
	return useMemo( () => {
		const result: ( FieldGroup | ColumnGroup )[] = [];
		let currentFields: Field< Product >[] = [];

		const flushCurrentFields = () => {
			if ( currentFields.length > 0 ) {
				result.push( {
					type: 'fields',
					content: currentFields,
				} );
				currentFields = [];
			}
		};

		fields.forEach( ( field ) => {
			const [ fieldName ] = field;

			// If this is a columns block
			if ( fieldName === 'core/columns' ) {
				flushCurrentFields();
				result.push( {
					type: 'column',
					content: field,
				} );
			} else {
				// Convert regular field
				const getFieldDefinition = getProductField( fieldName );
				const convertedField: Field< Product > = {
					...getFieldDefinition,
					id: getFieldKey( field ),
				};
				currentFields.push( convertedField );
			}
		} );

		// Don't forget remaining fields
		flushCurrentFields();

		return result;
	}, [ fields ] );
}
