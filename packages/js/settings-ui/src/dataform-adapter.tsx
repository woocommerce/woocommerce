/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import type {
	DataFormControlProps,
	Field,
	FieldType,
	Form,
	FormField,
	FormValidity,
	Rules,
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
	resolveGroupVisibilityPredicate,
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

export type DataFormAdapterOptions = {
	schema: SettingsUISchema;
	context: SettingsFieldContext;
	initialValues: SettingsValues;
	/**
	 * Validation rules per field id. Internal until the public
	 * validation surface is agreed.
	 */
	fieldRules?: Record< string, Rules< SettingsValues > >;
};

/**
 * A render section is either a run of consecutive groups rendered as one
 * DataForm with card-layout combined fields (the package-recommended
 * shape), or a single group that falls back to the shell-owned card
 * because the package card header has no slot for header actions.
 */
export type DataFormRenderSection =
	| {
			type: 'dataform';
			key: string;
			form: Form;
	  }
	| {
			type: 'fallback';
			key: string;
			form: Form;
			group: SettingsUIGroup;
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

// Legacy PHP settings deliver numeric values as strings, which the
// package number control and the number type validation reject. Present
// finite numbers to the package and let toNumberFieldValue restore the
// saved representation on the way back.
const toNumberControlValue = ( value: SettingsValue | undefined ) => {
	if ( value === '' || value === null || typeof value === 'undefined' ) {
		return undefined;
	}

	if ( typeof value === 'string' && value.trim() !== '' ) {
		const parsed = Number( value );

		if ( Number.isFinite( parsed ) ) {
			return parsed;
		}
	}

	return value;
};

const toNumberFieldValue = (
	next: SettingsValue,
	initialValue: SettingsValue | undefined
): SettingsValue => {
	// The native renderer already emits the legacy string vocabulary.
	if ( typeof next === 'string' ) {
		return next;
	}

	if ( typeof next === 'undefined' || next === null ) {
		return '';
	}

	return typeof initialValue === 'number' ? next : String( next );
};

export const areValuesEqual = ( a: SettingsValue, b: SettingsValue ) => {
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

	return expectedValues.some( ( expectedValue ) => {
		// Boolean expectations match any checkbox vocabulary, so rules
		// written against `true` work for `yes` and `1` values too.
		if ( typeof expectedValue === 'boolean' ) {
			return isCheckedValue( value ) === expectedValue;
		}

		return areValuesEqual( value, expectedValue );
	} );
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

// DataForm types descriptions as plain strings while the schema carries
// sanitized HTML. The layouts render the description as a node, so a
// sanitized element keeps links and formatting working despite the
// declared string type.
const toDescriptionNode = ( description?: string ) => {
	if ( ! description ) {
		return undefined;
	}

	if ( ! /<[^>]+>/.test( description ) ) {
		return description;
	}

	return createElement( 'span', {
		dangerouslySetInnerHTML: {
			__html: sanitizeSettingsHtml( description ),
		},
	} ) as unknown as string;
};

type TypeAndEdit = {
	type?: FieldType;
	Edit?: Field< SettingsValues >[ 'Edit' ];
};

const getPackageTypeAndEdit = ( field: SettingsUIField ): TypeAndEdit => {
	switch ( field.type ) {
		case 'checkbox':
			return { type: 'boolean', Edit: 'checkbox' };
		case 'radio':
			return { type: 'text', Edit: 'radio' };
		case 'select':
			return { type: 'text', Edit: 'select' };
		case 'textarea':
			return { type: 'text', Edit: { control: 'textarea' } };
		case 'number':
			return { type: 'number', Edit: 'number' };
		case 'tel':
			return { type: 'telephone', Edit: 'telephone' };
		case 'array':
			return { type: 'array' };
		case 'date':
		case 'email':
		case 'password':
		case 'text':
		case 'url':
			return { type: field.type, Edit: field.type };
		default:
			return {};
	}
};

// The package controls cannot express a disabled state or arbitrary
// input attributes (including min/max/step on number fields), so fields
// carrying them render through the native renderer until upstream
// supports them.
const needsNativeEdit = ( field: SettingsUIField ) =>
	Boolean( field.disabled ) ||
	Object.keys( field.customAttributes || {} ).length > 0;

const createNativeEdit = ( settingsField: SettingsUIField ) => {
	return function NativeFieldEdit( {
		data,
		field,
		onChange,
	}: DataFormControlProps< SettingsValues > ) {
		const value = data[ settingsField.id ];

		return (
			<div className="wc-settings-ui__field">
				<NativeSettingsField
					field={ settingsField }
					value={ typeof value === 'undefined' ? '' : value }
					onChange={ ( nextValue ) =>
						onChange(
							field.setValue( { item: data, value: nextValue } )
						)
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
					<span
						dangerouslySetInnerHTML={ {
							__html: sanitizeSettingsHtml(
								settingsField.description
							),
						} }
					/>
				) : null }
			</div>
		);
	};
};

// Registered components are DataForm Edit controls. Resolution happens
// on every render so late registrations behave the same as before.
const createRegisteredEdit = (
	settingsField: SettingsUIField,
	context: SettingsFieldContext,
	fallback: Field< SettingsValues >[ 'Edit' ]
) => {
	return function RegisteredFieldEdit(
		props: DataFormControlProps< SettingsValues >
	) {
		const Registered = resolveFieldComponent( settingsField, context );

		if ( Registered ) {
			return <Registered { ...props } />;
		}

		if ( typeof fallback === 'function' ) {
			const Fallback = fallback;
			return <Fallback { ...props } />;
		}

		return null;
	};
};

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

	if ( settingsField.type === 'number' ) {
		return ( { item }: { item: SettingsValues } ) =>
			toNumberControlValue( item[ settingsField.id ] );
	}

	return ( { item }: { item: SettingsValues } ) => {
		const value = item[ settingsField.id ];
		return typeof value === 'undefined' ? '' : value;
	};
};

const createSetValue = (
	settingsField: SettingsUIField,
	initialValues: SettingsValues
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
					initialValues[ settingsField.id ]
				),
			};
		}

		if ( settingsField.type === 'number' ) {
			return {
				[ settingsField.id ]: toNumberFieldValue(
					value,
					initialValues[ settingsField.id ]
				),
			};
		}

		return { [ settingsField.id ]: value };
	};
};

const createIsVisible = (
	settingsField: SettingsUIField,
	options: DataFormAdapterOptions
) => {
	return ( item: SettingsValues ) => {
		const predicate = resolveFieldVisibilityPredicate(
			settingsField.id,
			options.context
		);

		if ( predicate ) {
			try {
				return predicate( {
					values: item,
					initialValues: options.initialValues,
					context: options.context,
					schema: options.schema,
				} );
			} catch ( predicateError ) {
				warn(
					`Visibility predicate for field "${ settingsField.id }" failed. Rendering it visible.`,
					{ error: predicateError, context: options.context }
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

export const buildDataFormField = (
	settingsField: SettingsUIField,
	options: DataFormAdapterOptions
): Field< SettingsValues > => {
	const field: Field< SettingsValues > = {
		id: settingsField.id,
		label: settingsField.label,
		description: toDescriptionNode( settingsField.description ),
		placeholder: settingsField.placeholder,
		getValue: createGetValue( settingsField ),
		setValue: createSetValue( settingsField, options.initialValues ),
		isVisible: createIsVisible( settingsField, options ),
		isValid: options.fieldRules?.[ settingsField.id ],
	};

	if ( settingsField.options && settingsField.options.length > 0 ) {
		field.elements = toElements( settingsField.options );
	}

	if ( settingsField.type === 'info' ) {
		// The regular layout skips fields whose normalized Edit control
		// is null, including read-only render fields, so info needs a
		// control type even though the read-only path never mounts it.
		field.type = 'text';
		field.readOnly = true;
		field.render = createInfoRender( settingsField );
		return field;
	}

	const packageControl = getPackageTypeAndEdit( settingsField );
	field.type = packageControl.type;

	let defaultEdit: Field< SettingsValues >[ 'Edit' ] | undefined;

	if (
		settingsField.type === 'time' ||
		settingsField.type === 'datetime-local'
	) {
		// No package control exists for precise time inputs.
		field.type = 'text';
		defaultEdit = createNativeEdit( settingsField );
	} else if ( needsNativeEdit( settingsField ) ) {
		defaultEdit = createNativeEdit( settingsField );
	} else if (
		typeof packageControl.type === 'undefined' &&
		typeof packageControl.Edit === 'undefined'
	) {
		warn( `Field type "${ settingsField.type }" is not supported.`, {
			field: settingsField,
		} );
		field.type = 'text';
		defaultEdit = createNativeEdit( settingsField );
	} else {
		defaultEdit = packageControl.Edit;
	}

	const registeredAtBuild = Boolean(
		resolveFieldComponent( settingsField, options.context )
	);

	if ( registeredAtBuild || typeof defaultEdit === 'function' ) {
		field.Edit = createRegisteredEdit(
			settingsField,
			options.context,
			typeof defaultEdit === 'function' ? defaultEdit : undefined
		);
	} else if ( typeof defaultEdit !== 'undefined' ) {
		// String and config controls resolve inside DataForm; leaving
		// array fields without an Edit keeps the package's type default.
		field.Edit = defaultEdit;
	}

	return field;
};

// The card layout renders the combined field's description at the top of
// the card body, so group descriptions go through the package.
const getCardFormField = ( group: SettingsUIGroup ): FormField => ( {
	id: group.id,
	...( group.title ? { label: group.title } : {} ),
	...( group.description
		? { description: toDescriptionNode( group.description ) }
		: {} ),
	layout: {
		type: 'card',
		withHeader: Boolean( group.title ),
		isCollapsible: false,
	},
	children: group.fields.map( ( field ) => field.id ),
} );

export const getGroupValidity = (
	validity: FormValidity,
	groupId: string
): FormValidity => validity?.[ groupId ]?.children;

export const createDataFormAdapter = ( options: DataFormAdapterOptions ) => {
	const { schema } = options;
	const fields = Object.values( schema.groups ).flatMap( ( group ) =>
		group.fields.map( ( field ) => buildDataFormField( field, options ) )
	);
	const fieldsById = new Map(
		fields.map( ( field ) => [ field.id, field ] )
	);

	const isFieldVisible = ( fieldId: string, values: SettingsValues ) =>
		fieldsById.get( fieldId )?.isVisible?.( values ) !== false;

	const isGroupVisible = (
		group: SettingsUIGroup,
		values: SettingsValues
	) => {
		const predicate = resolveGroupVisibilityPredicate(
			group.id,
			options.context
		);

		if ( predicate ) {
			try {
				return predicate( {
					values,
					initialValues: options.initialValues,
					context: options.context,
					schema,
				} );
			} catch ( predicateError ) {
				warn(
					`Visibility predicate for group "${ group.id }" failed. Rendering it visible.`,
					{ error: predicateError, context: options.context }
				);
				return true;
			}
		}

		return true;
	};

	const getVisibleGroups = ( values: SettingsValues ) =>
		Object.values( schema.groups )
			.filter( ( group ) => isGroupVisible( group, values ) )
			.filter( ( group ) =>
				group.fields.some( ( field ) =>
					isFieldVisible( field.id, values )
				)
			);

	const getRenderSections = (
		values: SettingsValues
	): DataFormRenderSection[] => {
		const sections: DataFormRenderSection[] = [];
		let pendingCardFields: FormField[] = [];
		let segmentIndex = 0;

		const flushDataForm = () => {
			if ( pendingCardFields.length === 0 ) {
				return;
			}

			sections.push( {
				type: 'dataform',
				key: `dataform-${ segmentIndex }`,
				form: {
					layout: { type: 'card' },
					fields: pendingCardFields,
				},
			} );
			pendingCardFields = [];
			segmentIndex += 1;
		};

		getVisibleGroups( values ).forEach( ( group ) => {
			if ( ! group.actions?.length ) {
				pendingCardFields.push( getCardFormField( group ) );
				return;
			}

			flushDataForm();
			sections.push( {
				type: 'fallback',
				key: `fallback-${ group.id }`,
				group,
				form: {
					layout: { type: 'regular' },
					fields: group.fields.map( ( field ) => field.id ),
				},
			} );
		} );
		flushDataForm();

		return sections;
	};

	// Only visible fields validate, so a hidden required field can never
	// block saving.
	const getValidationForm = ( values: SettingsValues ): Form => ( {
		layout: { type: 'regular' },
		fields: getVisibleGroups( values ).map( ( group ) => ( {
			id: group.id,
			children: group.fields
				.filter( ( field ) => isFieldVisible( field.id, values ) )
				.map( ( field ) => field.id ),
		} ) ),
	} );

	return {
		fields,
		getRenderSections,
		getValidationForm,
	};
};
