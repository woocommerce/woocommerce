/**
 * External dependencies
 */
import { createElement, RawHTML } from '@wordpress/element';
import type {
	DataFormControlProps,
	Field,
	FieldType,
	Form,
	FormField,
} from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { warn } from './diagnostics';
import { sanitizeSettingsHtml } from './html';
import { NativeSettingsField } from './native-fields';
import {
	resolveFieldComponent,
	resolveFieldVisibilityPredicate,
} from './registry';
import type {
	SettingsFieldContext,
	SettingsUIField,
	SettingsUIGroup,
	SettingsUIOption,
	SettingsUISchema,
	SettingsValue,
	SettingsValues,
} from './types';

/**
 * Page-level state and helpers the adapter closes over when building
 * DataForm fields. The built fields must be rebuilt whenever any of
 * these change (the page memoizes on this object).
 */
export type DataFormAdapterRuntime = {
	schema: SettingsUISchema;
	context: SettingsFieldContext;
	initialValues: SettingsValues;
	setValue: ( fieldId: string, value: SettingsValue ) => void;
	setValues: ( values: Partial< SettingsValues > ) => void;
};

const toStringValue = ( value: SettingsValue | undefined ) =>
	value === null || typeof value === 'undefined' ? '' : String( value );

const isCheckedValue = ( value: SettingsValue | undefined ): boolean =>
	value === true || value === 1 || value === 'yes' || value === '1';

// Legacy PHP settings express checkbox state as yes/no, 1/0, or booleans
// depending on the source. Echo the initial representation back so the
// save flow round-trips values unchanged.
const toCheckboxValue = (
	checked: boolean,
	initialValue: SettingsValue | undefined
): SettingsValue => {
	if (
		typeof initialValue !== 'undefined' &&
		checked === isCheckedValue( initialValue )
	) {
		return initialValue;
	}

	if ( typeof initialValue === 'boolean' ) {
		return checked;
	}

	if ( initialValue === 1 || initialValue === 0 ) {
		return checked ? 1 : 0;
	}

	if ( initialValue === '1' || initialValue === '0' ) {
		return checked ? '1' : '0';
	}

	return checked ? 'yes' : 'no';
};

const areValuesEqual = ( a: SettingsValue, b: SettingsValue ) => {
	if ( Array.isArray( a ) || Array.isArray( b ) ) {
		return (
			Array.isArray( a ) &&
			Array.isArray( b ) &&
			a.length === b.length &&
			a.every( ( value, index ) => value === b[ index ] )
		);
	}

	return a === b;
};

const valueMatchesVisibilityRule = (
	value: SettingsValue,
	expected: SettingsValue | SettingsValue[] | undefined
) => {
	const expectedValues = Array.isArray( expected )
		? expected
		: [ expected ?? true ];

	return expectedValues.some( ( expectedValue ) =>
		areValuesEqual( value, expectedValue )
	);
};

// DataForm control help renders as plain text, so HTML descriptions are
// reduced to their text content for the package-native controls.
const toPlainTextDescription = ( description?: string ) => {
	if ( ! description ) {
		return undefined;
	}

	const text = sanitizeSettingsHtml( description )
		.replace( /<[^>]*>/g, ' ' )
		.replace( /\s+/g, ' ' )
		.trim();

	return text || undefined;
};

// PHP-supplied schemas can carry non-string option values at runtime, so
// selections are matched by string representation and mapped back to the
// original option value to preserve its type.
const restoreOptionValue = (
	next: SettingsValue,
	options?: SettingsUIOption[]
): SettingsValue => {
	const nextString = toStringValue( next );
	const option = ( options || [] ).find(
		( candidate ) => toStringValue( candidate.value ) === nextString
	);

	return typeof option === 'undefined' ? next : option.value;
};

const toElements = ( options?: SettingsUIOption[] ) =>
	( options || [] ).map( ( option ) => ( {
		value: toStringValue( option.value ),
		label: option.label,
	} ) );

const DATAFORM_FIELD_TYPES: Record< string, FieldType > = {
	// The regular layout skips fields whose normalized Edit control is
	// null, including read-only render fields, so info needs a control
	// type even though the read-only render path never mounts it.
	info: 'text',
	checkbox: 'boolean',
	number: 'number',
	text: 'text',
	password: 'password',
	email: 'email',
	url: 'url',
	tel: 'telephone',
	date: 'date',
	'datetime-local': 'datetime',
	time: 'text',
	textarea: 'text',
	select: 'text',
	radio: 'text',
	array: 'array',
};

export const getFieldTypeClassName = ( type: string ) =>
	`wc-settings-ui__field--${ type.replace( /[^a-z0-9_-]/gi, '-' ) }`;

const createGetValue = ( settingsField: SettingsUIField ) => {
	if ( settingsField.type === 'checkbox' ) {
		return ( { item }: { item: SettingsValues } ) =>
			isCheckedValue( item[ settingsField.id ] );
	}

	if ( settingsField.type === 'array' ) {
		return ( { item }: { item: SettingsValues } ) => {
			const value = item[ settingsField.id ];
			return Array.isArray( value ) ? value : [];
		};
	}

	if ( settingsField.type === 'select' || settingsField.type === 'radio' ) {
		return ( { item }: { item: SettingsValues } ) =>
			toStringValue( item[ settingsField.id ] );
	}

	return ( { item }: { item: SettingsValues } ) => {
		const value = item[ settingsField.id ];
		return typeof value === 'undefined' ? '' : value;
	};
};

const createSetValue = (
	settingsField: SettingsUIField,
	runtime: DataFormAdapterRuntime
) => {
	return ( {
		value,
	}: {
		item: SettingsValues;
		value: SettingsValue;
	} ): Partial< SettingsValues > => {
		if (
			settingsField.type === 'select' ||
			settingsField.type === 'radio'
		) {
			return {
				[ settingsField.id ]: restoreOptionValue(
					value,
					settingsField.options
				),
			};
		}

		if ( settingsField.type === 'array' ) {
			return {
				[ settingsField.id ]: ( Array.isArray( value )
					? value
					: []
				).map( String ),
			};
		}

		if ( settingsField.type === 'checkbox' ) {
			return {
				[ settingsField.id ]: toCheckboxValue(
					Boolean( value ),
					runtime.initialValues[ settingsField.id ]
				),
			};
		}

		return { [ settingsField.id ]: value };
	};
};

// Visibility predicates are resolved on every call so late extension
// registrations behave the same as with the previous per-render
// resolution.
const createIsVisible = (
	settingsField: SettingsUIField,
	runtime: DataFormAdapterRuntime
) => {
	return ( item: SettingsValues ) => {
		const predicate = resolveFieldVisibilityPredicate(
			settingsField.id,
			runtime.context
		);

		if ( predicate ) {
			try {
				return predicate( {
					values: item,
					initialValues: runtime.initialValues,
					context: runtime.context,
					schema: runtime.schema,
				} );
			} catch ( predicateError ) {
				warn(
					`Visibility predicate for field "${ settingsField.id }" failed. Rendering it visible.`,
					{ error: predicateError, context: runtime.context }
				);
				return true;
			}
		}

		if ( settingsField.visibility ) {
			return valueMatchesVisibilityRule(
				item[ settingsField.visibility.controller ],
				settingsField.visibility.value
			);
		}

		return true;
	};
};

// Bridges the DataForm control contract onto the settings field component
// contract, so registered extension components and the native renderers
// work unchanged inside DataForm.
const createBridgeEdit = (
	settingsField: SettingsUIField,
	runtime: DataFormAdapterRuntime
) => {
	return function BridgedSettingsField( {
		data,
		onChange,
	}: DataFormControlProps< SettingsValues > ) {
		const FieldComponent =
			resolveFieldComponent( settingsField, runtime.context ) ||
			NativeSettingsField;
		const value = data[ settingsField.id ];

		return (
			<div
				className={ [
					'wc-settings-ui__field',
					getFieldTypeClassName( settingsField.type ),
				].join( ' ' ) }
			>
				<FieldComponent
					field={ settingsField }
					value={ typeof value === 'undefined' ? '' : value }
					context={ runtime.context }
					values={ data }
					initialValues={ runtime.initialValues }
					setValue={ runtime.setValue }
					setValues={ runtime.setValues }
					onChange={ ( nextValue ) =>
						onChange( { [ settingsField.id ]: nextValue } )
					}
				/>
			</div>
		);
	};
};

const createInfoRender = ( settingsField: SettingsUIField ) => {
	return function InfoField() {
		return (
			<div className="wc-settings-ui__info" id={ settingsField.id }>
				<strong>{ settingsField.label }</strong>
				{ settingsField.description ? (
					<RawHTML>
						{ sanitizeSettingsHtml( settingsField.description ) }
					</RawHTML>
				) : null }
			</div>
		);
	};
};

// Field types rendered by the package's own DataForm controls when no
// extension component is registered. The remaining types stay on the
// bridge: their DataForm controls are @wordpress/components based while
// the design direction is @wordpress/ui, and control help is plain text
// while schema descriptions carry sanitized HTML.
const PACKAGE_CONTROL_TYPES = [ 'radio', 'array' ];

export const buildDataFormField = (
	settingsField: SettingsUIField,
	runtime: DataFormAdapterRuntime
): Field< SettingsValues > => {
	const field: Field< SettingsValues > = {
		id: settingsField.id,
		label: settingsField.label,
		type: DATAFORM_FIELD_TYPES[ settingsField.type ],
		getValue: createGetValue( settingsField ),
		setValue: createSetValue( settingsField, runtime ),
		isVisible: createIsVisible( settingsField, runtime ),
	};

	if ( settingsField.options && settingsField.options.length > 0 ) {
		field.elements = toElements( settingsField.options );
	}

	if ( settingsField.placeholder ) {
		field.placeholder = settingsField.placeholder;
	}

	if ( settingsField.type === 'info' ) {
		field.readOnly = true;
		field.render = createInfoRender( settingsField );
		return field;
	}

	const hasRegisteredComponent = Boolean(
		resolveFieldComponent( settingsField, runtime.context )
	);

	if (
		! hasRegisteredComponent &&
		PACKAGE_CONTROL_TYPES.includes( settingsField.type )
	) {
		field.description = toPlainTextDescription( settingsField.description );

		if ( settingsField.type === 'radio' ) {
			field.Edit = 'radio';
		}

		return field;
	}

	if (
		! hasRegisteredComponent &&
		! ( settingsField.type in DATAFORM_FIELD_TYPES ) &&
		settingsField.type !== 'info'
	) {
		warn( `Field type "${ settingsField.type }" is not supported.`, {
			field: settingsField,
		} );
	}

	field.Edit = createBridgeEdit( settingsField, runtime );

	return field;
};

export const buildDataFormFields = (
	group: SettingsUIGroup,
	runtime: DataFormAdapterRuntime
): Field< SettingsValues >[] =>
	group.fields.map( ( settingsField ) =>
		buildDataFormField( settingsField, runtime )
	);

// Info fields are read-only render fields; their label is part of the
// rendered block, so the layout label is suppressed.
export const buildDataFormFormConfig = ( group: SettingsUIGroup ): Form => ( {
	layout: { type: 'regular', labelPosition: 'top' },
	fields: group.fields.map( ( settingsField ): FormField | string =>
		settingsField.type === 'info'
			? {
					id: settingsField.id,
					layout: { type: 'regular', labelPosition: 'none' },
			  }
			: settingsField.id
	),
} );
