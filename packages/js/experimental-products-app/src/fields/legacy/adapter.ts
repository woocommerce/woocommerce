/**
 * External dependencies
 */
import {
	SelectControl,
	TextControl,
	TextareaControl,
	ToggleControl,
} from '@wordpress/components';
import { createElement } from '@wordpress/element';
import type { Field } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import type { ProductEntityRecord } from '../types';
import type { LegacyFieldDefinition, LegacyHookMapping } from './types';

type MetaDataEntry = { id?: number; key: string; value: string };
type FieldRef = { id: string; label: string };
const SAFE_HTML_ATTRIBUTES = new Set( [
	'min',
	'max',
	'step',
	'maxLength',
	'minLength',
	'pattern',
	'readonly',
	'disabled',
] );

function sanitizeCustomAttributes(
	attrs: Record< string, string >
): Record< string, string > {
	const safe: Record< string, string > = {};
	for ( const [ key, value ] of Object.entries( attrs ) ) {
		if ( SAFE_HTML_ATTRIBUTES.has( key ) ) {
			safe[ key ] = value;
		}
	}
	return safe;
}

type TextControlType =
	| 'text'
	| 'number'
	| 'email'
	| 'url'
	| 'tel'
	| 'password'
	| 'search'
	| 'date'
	| 'time'
	| 'datetime-local';

function getMetaValue( item: ProductEntityRecord, metaKey: string ): string {
	const entry = item.meta_data?.find(
		( m: MetaDataEntry ) => m.key === metaKey
	);
	return entry?.value ?? '';
}

function updateMetaData(
	data: ProductEntityRecord,
	metaKey: string,
	value: string,
	onChange: ( changes: Partial< ProductEntityRecord > ) => void
): void {
	const existing = data.meta_data ?? [];
	const idx = existing.findIndex( ( m: MetaDataEntry ) => m.key === metaKey );

	const updated =
		idx >= 0
			? existing.map( ( m: MetaDataEntry, i: number ) =>
					i === idx ? { ...m, value } : m
			  )
			: [ ...existing, { key: metaKey, value } ];

	onChange( { meta_data: updated } as Partial< ProductEntityRecord > );
}

export function parseVisibility(
	wrapperClass: string
): ( ( item: ProductEntityRecord ) => boolean ) | undefined {
	const patterns: Record< string, ( item: ProductEntityRecord ) => boolean > =
		{
			show_if_variation_manage_stock: ( item ) =>
				item.manage_stock === true,
			hide_if_variation_virtual: ( item ) =>
				( item as ProductEntityRecord & { virtual?: boolean } )
					.virtual !== true,
			show_if_variation_downloadable: ( item ) =>
				item.downloadable === true,
		};

	const checks: ( ( item: ProductEntityRecord ) => boolean )[] = [];
	for ( const [ pattern, check ] of Object.entries( patterns ) ) {
		if ( wrapperClass.includes( pattern ) ) {
			checks.push( check );
		}
	}

	if ( checks.length === 0 ) {
		return undefined;
	}

	return ( item ) => checks.every( ( check ) => check( item ) );
}

function createTextEdit(
	metaKey: string,
	opts: {
		placeholder?: string;
		help?: string;
		inputType?: string;
		customAttributes?: Record< string, string >;
	} = {}
) {
	return ( {
		data,
		field,
		onChange,
	}: {
		data: ProductEntityRecord;
		field: FieldRef;
		onChange: ( changes: Partial< ProductEntityRecord > ) => void;
	} ) =>
		createElement( TextControl, {
			__nextHasNoMarginBottom: true,
			label: field.label,
			help: opts.help,
			placeholder: opts.placeholder,
			type: ( opts.inputType || 'text' ) as TextControlType,
			value: getMetaValue( data, metaKey ),
			onChange: ( value: string ) =>
				updateMetaData( data, metaKey, value, onChange ),
			...sanitizeCustomAttributes( opts.customAttributes ?? {} ),
		} );
}

function createTextareaEdit(
	metaKey: string,
	opts: { placeholder?: string; help?: string } = {}
) {
	return ( {
		data,
		field,
		onChange,
	}: {
		data: ProductEntityRecord;
		field: FieldRef;
		onChange: ( changes: Partial< ProductEntityRecord > ) => void;
	} ) =>
		createElement( TextareaControl, {
			__nextHasNoMarginBottom: true,
			label: field.label,
			help: opts.help,
			placeholder: opts.placeholder,
			value: getMetaValue( data, metaKey ),
			onChange: ( value: string ) =>
				updateMetaData( data, metaKey, value, onChange ),
		} );
}

function createSelectEdit(
	metaKey: string,
	options: Record< string, string >,
	help?: string
) {
	const selectOptions = Object.entries( options ).map(
		( [ value, label ] ) => ( { value, label } )
	);

	return ( {
		data,
		field,
		onChange,
	}: {
		data: ProductEntityRecord;
		field: FieldRef;
		onChange: ( changes: Partial< ProductEntityRecord > ) => void;
	} ) =>
		createElement( SelectControl, {
			__nextHasNoMarginBottom: true,
			__next40pxDefaultSize: true,
			label: field.label,
			help,
			value: getMetaValue( data, metaKey ),
			options: selectOptions,
			onChange: ( value: string ) =>
				updateMetaData( data, metaKey, value, onChange ),
		} );
}

function createCheckboxEdit( metaKey: string, label: string, help?: string ) {
	return ( {
		data,
		onChange,
	}: {
		data: ProductEntityRecord;
		onChange: ( changes: Partial< ProductEntityRecord > ) => void;
	} ) => {
		const raw = getMetaValue( data, metaKey );
		return createElement( ToggleControl, {
			__nextHasNoMarginBottom: true,
			label,
			help,
			checked: raw === 'yes' || raw === '1',
			onChange: ( checked: boolean ) =>
				updateMetaData(
					data,
					metaKey,
					checked ? 'yes' : 'no',
					onChange
				),
		} );
	};
}

/**
 * Convert a legacy field definition into a DataForm Field object.
 *
 * Returns null for unsupported types with a console warning.
 */
export function createLegacyField(
	definition: LegacyFieldDefinition
): Field< ProductEntityRecord > | null {
	const fieldId = `legacy:${ definition.id }`;
	const metaKey = definition.meta_key;

	const baseField: Partial< Field< ProductEntityRecord > > = {
		id: fieldId,
		label: definition.label || definition.id,
		enableSorting: false,
		filterBy: false as const,
		getValue: ( { item } ) => getMetaValue( item, metaKey ),
	};

	const visibility = parseVisibility( definition.wrapper_class );
	if ( visibility ) {
		baseField.isVisible = visibility;
	}

	if ( definition.hidden ) {
		return {
			...baseField,
			type: 'text',
			isVisible: () => false,
		} as Field< ProductEntityRecord >;
	}

	const { description, placeholder, input_type, custom_attributes } =
		definition;
	const help = description || undefined;

	switch ( definition.type ) {
		case 'text_input':
			return {
				...baseField,
				type: 'text',
				Edit: createTextEdit( metaKey, {
					placeholder,
					help,
					inputType: input_type,
					customAttributes: custom_attributes,
				} ),
			} as Field< ProductEntityRecord >;

		case 'textarea_input':
			return {
				...baseField,
				type: 'text',
				Edit: createTextareaEdit( metaKey, {
					placeholder,
					help,
				} ),
			} as Field< ProductEntityRecord >;

		case 'select':
			return {
				...baseField,
				type: 'text',
				Edit: createSelectEdit( metaKey, definition.options, help ),
			} as Field< ProductEntityRecord >;

		case 'checkbox':
			return {
				...baseField,
				type: 'text',
				Edit: createCheckboxEdit(
					metaKey,
					definition.label || definition.id,
					help
				),
			} as Field< ProductEntityRecord >;

		case 'radio':
			return {
				...baseField,
				type: 'text',
				Edit: createSelectEdit( metaKey, definition.options, help ),
			} as Field< ProductEntityRecord >;

		default:
			// eslint-disable-next-line no-console
			console.warn(
				`Legacy field "${ definition.id }" uses unsupported type "${ definition.type }" and will be skipped.`
			);
			return null;
	}
}

/**
 * Merge legacy fields into a native field list based on hook mapping positions.
 */
export function insertLegacyFields(
	nativeFields: Field< ProductEntityRecord >[],
	legacyFieldsByHook: Record< string, Field< ProductEntityRecord >[] >,
	hookMapping: LegacyHookMapping
): Field< ProductEntityRecord >[] {
	const result = [ ...nativeFields ];

	for ( const [ hookName, position ] of Object.entries( hookMapping ) ) {
		const hookFields = legacyFieldsByHook[ hookName ];
		if ( ! hookFields || hookFields.length === 0 ) {
			continue;
		}

		if ( 'insertAt' in position && position.insertAt === 'end' ) {
			result.push( ...hookFields );
			continue;
		}

		if ( 'insertAfter' in position ) {
			const anchorIndex = result.findIndex(
				( f ) => f.id === position.insertAfter
			);
			if ( anchorIndex >= 0 ) {
				result.splice( anchorIndex + 1, 0, ...hookFields );
			} else {
				result.push( ...hookFields );
			}
		}
	}

	return result;
}
