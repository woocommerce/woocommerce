/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Parses options from different formats into a standardized array of option objects.
 *
 * @param {Object} options - The options to parse, either as a record or an array of objects.
 * @return {Array} An array of option objects with label, value, and optional description.
 */
export const parseOptions = ( options ) => {
	if ( ! options ) {
		return [];
	}

	if ( Array.isArray( options ) ) {
		return options.map( ( { label, value, desc } ) => ( {
			label: decodeEntities( label ),
			description: decodeEntities( desc ),
			value,
		} ) );
	}

	return Object.entries( options ).map( ( [ value, label ] ) => ( {
		label: decodeEntities( label ),
		value,
	} ) );
};

const fieldTypeTransformers = new Map();

export const registerFieldTypeTransformer = ( type, transformer ) => {
	if ( ! type || typeof transformer !== 'function' ) {
		return;
	}

	fieldTypeTransformers.set( type, transformer );
};

export const getFieldTypeTransformer = ( type ) =>
	fieldTypeTransformers.get( type );

/**
 * Reorders fields within a group based on desired order
 *
 * @param {string[]} fieldIds - Array of field IDs in their natural order
 * @param {string} groupId - ID of the group to reorder
 * @param {Record<string, string[]>} orderConfig - Custom field ordering configuration
 * @return {string[]} Array of field IDs in the desired order
 */
export const reorderGroupFields = ( fieldIds, groupId, orderConfig ) => {
	// Check if this group has a custom field order defined
	const desiredOrder = orderConfig[ groupId ];
	if ( ! desiredOrder ) {
		return fieldIds; // Return original order if no custom order is defined
	}

	const orderedFields = [];
	const remainingFields = [ ...fieldIds ];

	// Add fields in the desired order
	for ( const fieldId of desiredOrder ) {
		const index = remainingFields.indexOf( fieldId );
		if ( index !== -1 ) {
			orderedFields.push( fieldId );
			remainingFields.splice( index, 1 );
		}
	}

	// Add any remaining fields that weren't in the desired order
	return [ ...orderedFields, ...remainingFields ];
};

/**
 * Creates form children with row groupings for specified field pairs.
 *
 * @param {string[]} fieldIds - Array of field IDs in their natural order
 * @param {Array<{id: string, fields: string[]}>} rowConfigs - Array of row configurations, each with id and field pairs
 * @return {Array} Array of children with row groups and individual fields
 */
export const createChildrenWithRows = ( fieldIds, rowConfigs ) => {
	// Check which rows defined have all their fields available
	const availableRows = rowConfigs.filter( ( config ) =>
		config.fields.every( ( fieldId ) => fieldIds.includes( fieldId ) )
	);

	// Get all fields that are part of available rows
	const rowFields = availableRows.flatMap( ( config ) => config.fields );

	return fieldIds.reduce( ( acc, fieldId ) => {
		// Find if this field is the first field in any available row
		const rowConfig = availableRows.find(
			( config ) => config.fields[ 0 ] === fieldId
		);

		if ( rowConfig ) {
			acc.push( {
				id: rowConfig.id,
				layout: { type: 'row' },
				children: rowConfig.fields,
			} );
		} else if ( ! rowFields.includes( fieldId ) ) {
			acc.push( { id: fieldId } );
		}

		return acc;
	}, [] );
};

/**
 * Base field transformer with common field type handling
 *
 * @param {Object} setting - The setting to transform.
 * @return {Object} The transformed field object.
 */
export const baseFieldTransformer = ( setting ) => {
	const baseField = {
		id: setting.id,
		label: decodeEntities( setting.label ),
		type: setting.type,
	};

	// Only add description if it exists and is not empty
	if ( setting.desc && setting.desc.trim() !== '' ) {
		baseField.description = decodeEntities( setting.desc );
	}

	const customTransformer = getFieldTypeTransformer( setting.type );
	if ( customTransformer ) {
		return customTransformer( setting, baseField );
	}

	switch ( setting.type ) {
		case 'select':
			return {
				...baseField,
				type: 'select',
				elements: parseOptions( setting.options ),
			};
		case 'number':
			return {
				...baseField,
				type: 'integer',
			};
		case 'checkbox':
			return {
				...baseField,
				type: 'boolean',
				getValue: ( { item } ) => {
					const value = item[ setting.id ];
					return value === 'yes' || value === true;
				},
				setValue: ( { item, value } ) => {
					return {
						...item,
						[ setting.id ]: value ? 'yes' : 'no',
					};
				},
			};
		case 'radio':
			return {
				...baseField,
				type: 'text',
				Edit: 'radio',
				elements: parseOptions( setting.options ),
			};
		case 'text':
			return {
				...baseField,
				type: 'text',
			};
		case 'toggle':
			return {
				...baseField,
				type: 'boolean',
				Edit: 'toggle',
				getValue: ( { item } ) => {
					const value = item[ setting.id ];
					return value === 'yes' || value === true;
				},
				setValue: ( { item, value } ) => {
					return {
						...item,
						[ setting.id ]: value ? 'yes' : 'no',
					};
				},
			};
		case 'multiselect': {
			const optionValues = parseOptions( setting.options ).map(
				( option ) => option.value
			);
			return {
				...baseField,
				type: 'array',
				elements: parseOptions( setting.options ),
				isValid: ( value ) => {
					return (
						Array.isArray( value ) &&
						value.every( ( item ) => optionValues.includes( item ) )
					);
				},
			};
		}
		default:
			// Return a read-only text field for unsupported field types
			// This prevents silent failures when rendering unsupported fields
			return {
				id: setting.id,
				type: 'text',
				Edit: () => (
					<div>
						{ __(
							'This setting is not available yet.',
							'woocommerce'
						) }
					</div>
				),
			};
	}
};

if ( typeof window !== 'undefined' ) {
	const windowWithRegistry = window;
	windowWithRegistry.wcReactSettings =
		windowWithRegistry.wcReactSettings || {};
	windowWithRegistry.wcReactSettings.registerFieldTypeTransformer =
		registerFieldTypeTransformer;
}

/**
 * Hides the label of a form field if it has no label text.
 *
 * @param {Object} field - The field to modify.
 * @return {Object} The modified field.
 */
export const hideEmptyLabel = ( field ) => ( {
	id: field.id,
	layout: field.label
		? undefined
		: { type: 'regular', labelPosition: 'none' },
} );
