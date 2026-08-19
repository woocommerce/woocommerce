/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import type {
	Field,
	FieldTypeName,
	Form,
	FormField,
	Rules,
} from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { error } from './diagnostics';
import { createSettingsHelpElement, sanitizeSettingsHtml } from './html';
import {
	resolveFieldComponent,
	resolveFieldVisibilityPredicate,
	resolveGroupVisibilityPredicate,
} from './registry';
import type {
	SettingsFieldContext,
	SettingsUIField,
	SettingsUIGroup,
	SettingsUISchema,
	SettingsValues,
	SettingsVisibilityPredicate,
} from './types';
import { valueMatchesVisibilityRule } from './values';

// The adapter assumes the canonical value vocabulary from the PHP schema
// builder, so no value coercion happens here.

export type DataFormAdapterOptions = {
	schema: SettingsUISchema;
	context: SettingsFieldContext;
	initialValues: SettingsValues;
};

export type DataFormAdapter = {
	fields: Field< SettingsValues >[];
	getForm: ( values: SettingsValues ) => Form;
};

// FormField descriptions are plain strings, so group descriptions lose markup.
const toPlainText = ( html?: string ) => {
	if ( ! html ) {
		return undefined;
	}

	const container = document.createElement( 'div' );
	container.innerHTML = sanitizeSettingsHtml( html );
	return container.textContent || undefined;
};

type SettingsTypeDescriptor = {
	type: FieldTypeName;
	// Only named where the type alone resolves the wrong control. DataForm
	// derives the control from the field type otherwise, so naming one here
	// would restate its own default.
	edit?: string;
};

const settingsTypeDescriptors: Record< string, SettingsTypeDescriptor > = {
	text: { type: 'text' },
	password: { type: 'password' },
	number: { type: 'number' },
	checkbox: { type: 'boolean' },
	email: { type: 'email' },
	url: { type: 'url' },
	tel: { type: 'telephone' },
	date: { type: 'date' },
	'datetime-local': { type: 'datetime' },
	// DataForm has no time type, so the value rides in a text control.
	time: { type: 'text' },
	// Read-only display text. The type is here so DataForm resolves a control
	// and keeps the field; the renderer paints the description over it.
	info: { type: 'text' },
	// DataForm has no textarea or radio type, so these name the control the
	// schema asked for.
	textarea: { type: 'text', edit: 'textarea' },
	radio: { type: 'text', edit: 'radio' },
	// Closed lists name their control because DataForm only infers a select
	// from a non-empty elements list, and these types keep their meaning when
	// the list comes back empty. Array also has to be named because DataForm
	// defaults it to a free-text token field.
	select: { type: 'text', edit: 'select' },
	array: { type: 'array', edit: 'select' },
};

// Predicates fail open: a broken visibility callback renders the field or
// group rather than hiding it. The failure logs unconditionally because
// failing open can expose a field that was meant to stay hidden.
const runVisibilityPredicate = (
	predicate: SettingsVisibilityPredicate,
	kind: 'field' | 'group',
	id: string,
	values: SettingsValues,
	options: DataFormAdapterOptions
) => {
	try {
		// Coerce with the truthiness DataForm's layout applies, so a loose
		// predicate result cannot make the form filter and the layout
		// disagree about a field.
		return Boolean(
			predicate( {
				values,
				initialValues: options.initialValues,
				context: options.context,
				schema: options.schema,
			} )
		);
	} catch ( predicateError ) {
		error(
			`Visibility predicate for ${ kind } "${ id }" failed. Rendering it visible.`,
			{ error: predicateError, context: options.context }
		);
		return true;
	}
};

// Predicates resolve on every evaluation, so an extension that registers a
// predicate after the adapter is built still takes effect.
const createIsVisible = (
	settingsField: SettingsUIField,
	options: DataFormAdapterOptions
): Field< SettingsValues >[ 'isVisible' ] => {
	return ( item ) => {
		const predicate = resolveFieldVisibilityPredicate(
			settingsField.id,
			options.context
		);

		if ( predicate ) {
			return runVisibilityPredicate(
				predicate,
				'field',
				settingsField.id,
				item,
				options
			);
		}

		const visibility = settingsField.visibility;
		return visibility
			? valueMatchesVisibilityRule(
					item[ visibility.controller ],
					visibility.value
			  )
			: true;
	};
};

// HTML boolean attributes use presence semantics: disabled="false" still
// disables, while a boolean false stays unset.
const isAttributeSet = ( value: string | number | boolean | undefined ) =>
	typeof value !== 'undefined' && value !== false;

const isFieldDisabled = ( settingsField: SettingsUIField ) =>
	Boolean( settingsField.disabled ) ||
	isAttributeSet( settingsField.customAttributes?.disabled );

// Range constraints only validate against matching value types: numbers for
// number fields, date strings for date fields. Other types have no range
// rule slot in DataForm.
const toRangeConstraint = (
	value: string | number | boolean | undefined,
	type: FieldTypeName | undefined
) => {
	if (
		typeof value === 'boolean' ||
		typeof value === 'undefined' ||
		value === ''
	) {
		return undefined;
	}

	if ( type === 'number' ) {
		const numeric = Number( value );
		return Number.isFinite( numeric ) ? numeric : undefined;
	}

	if ( type === 'date' || type === 'datetime' ) {
		return String( value );
	}

	return undefined;
};

const toLengthConstraint = ( value: string | number | boolean | undefined ) => {
	if (
		typeof value === 'boolean' ||
		typeof value === 'undefined' ||
		value === ''
	) {
		return undefined;
	}

	const numeric = Number( value );
	return Number.isFinite( numeric ) ? numeric : undefined;
};

// Classic settings express constraints as HTML custom_attributes; map the
// ones with DataForm rule slots. Step stays unmapped because DataForm derives
// it from format.decimals rather than a rule.
const buildValidationRules = (
	settingsField: SettingsUIField,
	descriptor: SettingsTypeDescriptor | undefined
): Rules< SettingsValues > => {
	const attributes = settingsField.customAttributes ?? {};
	const rules: Rules< SettingsValues > = {};

	if ( isAttributeSet( attributes.required ) ) {
		rules.required = true;
	}

	const min = toRangeConstraint( attributes.min, descriptor?.type );
	if ( typeof min !== 'undefined' ) {
		rules.min = min;
	}

	const max = toRangeConstraint( attributes.max, descriptor?.type );
	if ( typeof max !== 'undefined' ) {
		rules.max = max;
	}

	const minLength = toLengthConstraint( attributes.minlength );
	if ( typeof minLength !== 'undefined' ) {
		rules.minLength = minLength;
	}

	const maxLength = toLengthConstraint( attributes.maxlength );
	if ( typeof maxLength !== 'undefined' ) {
		rules.maxLength = maxLength;
	}

	if ( typeof attributes.pattern === 'string' && attributes.pattern !== '' ) {
		rules.pattern = attributes.pattern;
	}

	return rules;
};

export const buildDataFormField = (
	settingsField: SettingsUIField,
	options: DataFormAdapterOptions
): Field< SettingsValues > => {
	const descriptor = Object.prototype.hasOwnProperty.call(
		settingsTypeDescriptors,
		settingsField.type
	)
		? settingsTypeDescriptors[ settingsField.type ]
		: undefined;
	const registeredComponent = resolveFieldComponent(
		settingsField,
		options.context
	);

	const field: Field< SettingsValues > = {
		id: settingsField.id,
		label: settingsField.label,
		description: createSettingsHelpElement( settingsField.description ),
		placeholder: settingsField.placeholder,
		type: descriptor?.type,
		elements: settingsField.options,
		isValid: buildValidationRules( settingsField, descriptor ),
		isVisible: createIsVisible( settingsField, options ),
		isDisabled: isFieldDisabled( settingsField ),
	};

	if ( registeredComponent ) {
		// A registered control accepts a frozen subset of the DataForm control
		// props, so the wider package props remain assignable to it.
		field.Edit = registeredComponent as Field< SettingsValues >[ 'Edit' ];
		return field;
	}

	// A field declaring a component requires that custom control. Failing
	// closed beats silently rendering a native field in its place.
	if ( settingsField.component ) {
		throw new Error(
			`Component "${ settingsField.component }" is not registered.`
		);
	}

	if ( settingsField.type === 'info' ) {
		field.readOnly = true;
		// DataForm paints the label for a read-only field and drops its
		// description, so info shows the sanitized element the field already
		// carries rather than sanitizing the same string again per render.
		field.render = ( { field: normalizedField } ) =>
			normalizedField.description ? (
				<div className="wc-settings-ui__info">
					{ normalizedField.description }
				</div>
			) : null;
		return field;
	}

	// Registered renderers resolve above, so reaching here means nothing can
	// draw the field. Failing closed beats dropping it beside a live Save
	// button, and matches the page this renderer replaces.
	if ( ! descriptor ) {
		throw new Error(
			`Field type "${ settingsField.type }" is not supported.`
		);
	}

	if ( descriptor.edit ) {
		field.Edit = descriptor.edit;
	}

	return field;
};

// FormField.description only accepts a plain string, so a group description
// keeps its text and loses its markup until DataForm accepts an element.
const buildGroupFormField = ( group: SettingsUIGroup ): FormField => ( {
	id: group.id,
	label: group.title || undefined,
	description: toPlainText( group.description ),
	layout: group.title
		? { type: 'card', isCollapsible: false }
		: { type: 'card', withHeader: false },
	children: group.fields.map( ( field ) => field.id ),
} );

export const createDataFormAdapter = (
	options: DataFormAdapterOptions
): DataFormAdapter => {
	const groups = Object.values( options.schema.groups );
	const fields = groups.flatMap( ( group ) =>
		group.fields.map( ( field ) => buildDataFormField( field, options ) )
	);
	const fieldsById = new Map(
		fields.map( ( field ) => [ field.id, field ] )
	);

	// A field without an isVisible callback is shown, matching DataForm.
	const isFieldVisible = ( fieldId: string, values: SettingsValues ) =>
		Boolean( fieldsById.get( fieldId )?.isVisible?.( values ) ?? true );

	const isGroupVisible = (
		group: SettingsUIGroup,
		values: SettingsValues
	) => {
		const predicate = resolveGroupVisibilityPredicate(
			group.id,
			options.context
		);
		if (
			predicate &&
			! runVisibilityPredicate(
				predicate,
				'group',
				group.id,
				values,
				options
			)
		) {
			return false;
		}

		return group.fields.some( ( field ) =>
			isFieldVisible( field.id, values )
		);
	};

	const getForm = ( values: SettingsValues ): Form => ( {
		fields: groups
			.filter( ( group ) => isGroupVisible( group, values ) )
			.map( buildGroupFormField ),
	} );

	return { fields, getForm };
};
