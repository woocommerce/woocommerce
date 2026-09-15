/**
 * External dependencies
 */
import type { Field, Form } from '@wordpress/dataviews';
import type { Settings } from '@woocommerce-settings-ui-experimental/settings-ui';

export type LegacyField = {
	id: string;
	label: string;
	type: NonNullable< Field< Settings >[ 'type' ] >;
	description: string;
	placeholder?: string;
	options?: { value: string; label: string }[];
	min?: number;
	max?: number;
	step?: number;
	multiline?: boolean;
};

export type LegacyConfig = {
	form: Form;
	fields: LegacyField[];
	values: Record< string, unknown >;
	unsupported: { id: string; label?: string | null; type: string; section: string }[];
};

type FieldEntry = NonNullable< Form[ 'fields' ] >[ number ];

export function getFieldIds( entries: Form[ 'fields' ] = [] ): Set< string > {
	const ids = new Set< string >();
	const visit = ( entry: FieldEntry ) => {
		if ( typeof entry === 'string' ) {
			ids.add( entry );
		} else if ( entry.children?.length ) {
			entry.children.forEach( visit );
		} else {
			ids.add( entry.id );
		}
	};
	entries?.forEach( visit );
	return ids;
}

function excludeFields( entries: Form[ 'fields' ], excluded: Set< string > ): FieldEntry[] {
	return ( entries ?? [] ).reduce< FieldEntry[] >( ( kept, entry ) => {
		if ( typeof entry === 'string' ) {
			if ( ! excluded.has( entry ) ) kept.push( entry );
		} else if ( entry.children ) {
			const children = excludeFields( entry.children, excluded );
			if ( children.length ) kept.push( { ...entry, children } );
		} else if ( ! excluded.has( entry.id ) ) {
			kept.push( entry );
		}
		return kept;
	}, [] );
}

/** Return the classic cards that can be appended to the main DataForm form. */
export function getLegacyCards( mainForm: Form | undefined, legacy: LegacyConfig | undefined ) {
	return excludeFields( legacy?.form.fields, getFieldIds( mainForm?.fields ) );
}

function toField( field: LegacyField ): Field< Settings > {
	return {
		id: field.id,
		label: field.label,
		type: field.type,
		description: field.description,
		placeholder: field.placeholder,
		...( field.options ? { elements: field.options } : {} ),
		...( field.min !== undefined || field.max !== undefined
			? { isValid: { min: field.min, max: field.max } } : {} ),
		// DataViews chooses edit controls from field.type; text needs this hint for a multiline control.
		...( field.multiline ? { Edit: 'textarea' } : {} ),
	};
}

/** Merge field definitions, preserving the existing client definition for a matching ID. */
export function getDataFormFields(
	mainFields: Field< Settings >[],
	legacy: LegacyConfig | undefined,
	form: Form
) {
	const byId = new Map< string, Field< Settings > >();
	mainFields.forEach( ( field ) => byId.set( field.id, field ) );
	legacy?.fields.forEach( ( field ) => {
		if ( ! byId.has( field.id ) ) byId.set( field.id, toField( field ) );
	} );
	const visible = getFieldIds( form.fields );
	return Array.from( byId.values() ).filter( ( field ) => visible.has( field.id ) );
}
