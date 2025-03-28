/**
 * External dependencies
 */
import { useMemo } from '@wordpress/element';
import { Field } from '@wordpress/dataviews';
import { Product } from '@woocommerce/data';

/**
 * Internal dependencies
 */
import { getProductField } from './fields';

type FieldDefinition = [
	string,
	{
		type?: string;
		label?: string;
		description?: string;
		property?: string;
		metadata?: {
			bindings?: {
				value?: {
					source?: string;
					args?: {
						prop?: string;
					};
				};
			};
		};
		[ key: string ]: unknown;
	}
];

function getFieldKey( field: FieldDefinition ): string {
	const attributes = field[ 1 ];
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
	fields: FieldDefinition[] = []
): Field< Product >[] {
	return useMemo( () => {
		return fields.map( ( [ fieldName, params ] ) => {
			const getFieldDefinition = getProductField( fieldName );
			console.log( fieldName, getFieldDefinition );
			// Convert the field definition to a DataForm field format
			const field: Field< Product > = {
				...getFieldDefinition,
				id: getFieldKey( [ fieldName, params ] ),
			};

			// Note: In practice, you'd want to:
			// 1. Have a mapping of fieldNames to their respective Edit components
			// 2. Or use React.lazy with dynamic imports for code splitting
			// Example with React.lazy:
			// field.Edit = React.lazy(() => import(`../../blocks/${fieldName}/edit`));

			return field;
		} );
	}, [ fields ] );
}
