/**
 * External dependencies
 */
import { __ } from '@wordpress/i18n';
import { decodeEntities } from '@wordpress/html-entities';

/**
 * Internal dependencies
 */
import { ErrorBoundary } from '../error-boundary';
import type {
	ReactSettingsField,
	ReactSettingsFieldOptions,
	ReactSettingsOptionItem,
} from './types';

type ReactSettingsParsedOption = {
	label: string;
	value: string;
	description?: string;
};

type BaseField = {
	id: string;
	label: string;
	type: string;
	description?: string;
};

type FieldTypeTransformer = (
	setting: ReactSettingsField,
	baseField: BaseField
) => Record< string, unknown >;

type EditComponent = {
	( props: Record< string, unknown > ): JSX.Element | null;
	displayName?: string;
};

const coerceString = ( value: unknown ): string | null => {
	if ( value === null || value === undefined ) {
		return null;
	}

	if ( typeof value === 'string' || typeof value === 'number' ) {
		return String( value );
	}

	if ( typeof value === 'boolean' ) {
		return value ? 'true' : 'false';
	}

	return null;
};

/**
 * Parses options from different formats into a standardized array of option objects.
 *
 * @param options - The options to parse, either as a record or an array of objects.
 * @return An array of option objects with label, value, and optional description.
 */
export const parseOptions = (
	options?: ReactSettingsFieldOptions
): ReactSettingsParsedOption[] => {
	if ( ! options ) {
		return [];
	}

	if ( Array.isArray( options ) ) {
		return options
			.map(
				(
					option: ReactSettingsOptionItem
				): ReactSettingsParsedOption | null => {
					const label = coerceString( option.label );
					const value = coerceString( option.value );
					if ( ! label || ! value ) {
						return null;
					}

					const description =
						coerceString( option.desc ) ??
						coerceString( option.description );

					return {
						label: decodeEntities( label ),
						...( description
							? { description: decodeEntities( description ) }
							: {} ),
						value,
					};
				}
			)
			.filter(
				( option ): option is ReactSettingsParsedOption =>
					option !== null
			);
	}

	return Object.entries( options )
		.map( ( [ value, label ] ) => {
			const safeLabel = coerceString( label );
			if ( ! safeLabel ) {
				return null;
			}

			return {
				label: decodeEntities( safeLabel ),
				value,
			};
		} )
		.filter(
			( option ): option is ReactSettingsParsedOption => option !== null
		);
};

const fieldTypeTransformers = new Map< string, FieldTypeTransformer >();

export const registerFieldTypeTransformer = (
	type: string,
	transformer: FieldTypeTransformer
) => {
	if ( ! type || typeof transformer !== 'function' ) {
		return;
	}

	fieldTypeTransformers.set( type, transformer );
};

export const getFieldTypeTransformer = ( type: string ) =>
	fieldTypeTransformers.get( type );

const withFieldErrorBoundary = ( Edit: EditComponent ): EditComponent => {
	const Wrapped = ( props: Record< string, unknown > ) => (
		<ErrorBoundary>
			<Edit { ...props } />
		</ErrorBoundary>
	);

	Wrapped.displayName = `SafeEdit(${
		Edit.displayName || Edit.name || 'Component'
	})`;

	return Wrapped;
};

const applySafeEdit = ( field: Record< string, unknown > ) => {
	const edit = field.Edit;
	if ( typeof edit === 'function' ) {
		return {
			...field,
			Edit: withFieldErrorBoundary( edit as EditComponent ),
		};
	}

	return field;
};

/**
 * Reorders fields within a group based on desired order.
 *
 * @param fieldIds    - Array of field IDs in their natural order.
 * @param groupId     - ID of the group to reorder.
 * @param orderConfig - Custom field ordering configuration.
 *
 * @return Array of field IDs in the desired order.
 */
export const reorderGroupFields = (
	fieldIds: string[],
	groupId: string,
	orderConfig: Record< string, string[] >
): string[] => {
	// Check if this group has a custom field order defined.
	const desiredOrder = orderConfig[ groupId ];
	if ( ! desiredOrder ) {
		return fieldIds; // Return original order if no custom order is defined.
	}

	const orderedFields: string[] = [];
	const remainingFields = [ ...fieldIds ];

	// Add fields in the desired order.
	for ( const fieldId of desiredOrder ) {
		const index = remainingFields.indexOf( fieldId );
		if ( index !== -1 ) {
			orderedFields.push( fieldId );
			remainingFields.splice( index, 1 );
		}
	}

	// Add any remaining fields that weren't in the desired order.
	return [ ...orderedFields, ...remainingFields ];
};

type RowConfig = {
	id: string;
	fields: string[];
};

type RowChild = {
	id: string;
	layout?: { type: 'row' };
	children?: string[];
};

/**
 * Creates form children with row groupings for specified field pairs.
 *
 * @param fieldIds   - Array of field IDs in their natural order.
 * @param rowConfigs - Array of row configurations, each with id and field pairs.
 * @return Array of children with row groups and individual fields.
 */
export const createChildrenWithRows = (
	fieldIds: string[],
	rowConfigs: RowConfig[]
): RowChild[] => {
	// Check which rows defined have all their fields available.
	const availableRows = rowConfigs.filter( ( config ) =>
		config.fields.every( ( fieldId ) => fieldIds.includes( fieldId ) )
	);

	// Get all fields that are part of available rows.
	const rowFields = availableRows.flatMap( ( config ) => config.fields );

	return fieldIds.reduce< RowChild[] >( ( acc, fieldId ) => {
		// Find if this field is the first field in any available row.
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
 * Base field transformer with common field type handling.
 *
 * @param setting - The setting to transform.
 * @return The transformed field object.
 */
export const baseFieldTransformer = (
	setting: ReactSettingsField
): Record< string, unknown > => {
	const baseField: BaseField = {
		id: setting.id,
		label: decodeEntities( setting.label ),
		type: setting.type,
	};

	// Only add description if it exists and is not empty.
	if ( setting.desc && setting.desc.trim() !== '' ) {
		baseField.description = decodeEntities( setting.desc );
	}

	const customTransformer = getFieldTypeTransformer( setting.type );
	if ( customTransformer ) {
		const customField = customTransformer( setting, baseField );
		if ( customField && typeof customField === 'object' ) {
			return applySafeEdit( customField as Record< string, unknown > );
		}

		return baseField;
	}

	switch ( setting.type ) {
		case 'select': {
			const field = {
				...baseField,
				type: 'select',
				elements: parseOptions( setting.options ),
			};
			return applySafeEdit( field );
		}
		case 'number': {
			const field = {
				...baseField,
				type: 'integer',
			};
			return applySafeEdit( field );
		}
		case 'checkbox': {
			const field = {
				...baseField,
				type: 'boolean',
				getValue: ( { item }: { item: Record< string, unknown > } ) => {
					const value = item[ setting.id ];
					return value === 'yes' || value === true;
				},
				setValue: ( {
					item,
					value,
				}: {
					item: Record< string, unknown >;
					value: boolean;
				} ) => {
					return {
						...item,
						[ setting.id ]: value ? 'yes' : 'no',
					};
				},
			};
			return applySafeEdit( field );
		}
		case 'radio': {
			const field = {
				...baseField,
				type: 'text',
				Edit: 'radio',
				elements: parseOptions( setting.options ),
			};
			return applySafeEdit( field );
		}
		case 'text': {
			const field = {
				...baseField,
				type: 'text',
			};
			return applySafeEdit( field );
		}
		case 'toggle': {
			const field = {
				...baseField,
				type: 'boolean',
				Edit: 'toggle',
				getValue: ( { item }: { item: Record< string, unknown > } ) => {
					const value = item[ setting.id ];
					return value === 'yes' || value === true;
				},
				setValue: ( {
					item,
					value,
				}: {
					item: Record< string, unknown >;
					value: boolean;
				} ) => {
					return {
						...item,
						[ setting.id ]: value ? 'yes' : 'no',
					};
				},
			};
			return applySafeEdit( field );
		}
		case 'multiselect': {
			const optionValues = parseOptions( setting.options ).map(
				( option ) => option.value
			);
			const field = {
				...baseField,
				type: 'array',
				elements: parseOptions( setting.options ),
				isValid: ( value: unknown ) => {
					return (
						Array.isArray( value ) &&
						value.every( ( item ) => optionValues.includes( item ) )
					);
				},
			};
			return applySafeEdit( field );
		}
		default: {
			const UnsupportedFieldEdit = () => (
				<div>
					{ __(
						'This setting is not available yet.',
						'woocommerce'
					) }
				</div>
			);

			const field = {
				id: setting.id,
				type: 'text',
				Edit: UnsupportedFieldEdit,
			};
			return applySafeEdit( field );
		}
	}
};

if ( typeof window !== 'undefined' ) {
	const windowWithRegistry = window as Window & {
		wcReactSettings?: {
			registerFieldTypeTransformer?: typeof registerFieldTypeTransformer;
		};
	};
	windowWithRegistry.wcReactSettings =
		windowWithRegistry.wcReactSettings || {};
	windowWithRegistry.wcReactSettings.registerFieldTypeTransformer =
		registerFieldTypeTransformer;
}

/**
 * Hides the label of a form field if it has no label text.
 *
 * @param field - The field to modify.
 * @return The modified field.
 */
export const hideEmptyLabel = ( field: ReactSettingsField ) => ( {
	id: field.id,
	layout: field.label
		? undefined
		: { type: 'regular', labelPosition: 'none' },
} );
