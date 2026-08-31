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
import { error, warn } from './diagnostics';
import { createSettingsHelpElement } from './html';
import {
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
// builder and how extension components attach is a renderer concern, so
// neither value coercion nor component registry resolution happens here.

export type DataFormAdapterOptions = {
	schema: SettingsUISchema;
	context: SettingsFieldContext;
	initialValues: SettingsValues;
};

export type DataFormAdapter = {
	fields: Field< SettingsValues >[];
	getForm: ( values: SettingsValues ) => Form;
};

type SettingsTypeDescriptor = {
	type: FieldTypeName;
	edit: string;
};

const settingsTypeDescriptors: Record< string, SettingsTypeDescriptor > = {
	checkbox: { type: 'boolean', edit: 'checkbox' },
	select: { type: 'text', edit: 'select' },
	radio: { type: 'text', edit: 'radio' },
	textarea: { type: 'text', edit: 'textarea' },
	number: { type: 'number', edit: 'number' },
	// The select control renders as a closed multi-select for array fields,
	// matching the `select multiple` the native renderer uses.
	array: { type: 'array', edit: 'select' },
	text: { type: 'text', edit: 'text' },
	password: { type: 'password', edit: 'password' },
	'datetime-local': { type: 'datetime', edit: 'datetime' },
	date: { type: 'date', edit: 'date' },
	// DataForm has no time control; the plain text control carries the value.
	time: { type: 'text', edit: 'text' },
	email: { type: 'email', edit: 'email' },
	url: { type: 'url', edit: 'url' },
	tel: { type: 'telephone', edit: 'telephone' },
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
// ones with DataForm rule slots. The closed elements rule stays off because
// stored values can predate the current options, and step stays unmapped
// because DataForm derives it from format.decimals rather than a rule.
const buildValidationRules = (
	settingsField: SettingsUIField,
	descriptor: SettingsTypeDescriptor | undefined
): Rules< SettingsValues > => {
	const attributes = settingsField.customAttributes ?? {};
	const rules: Rules< SettingsValues > = { elements: false };

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
	const descriptor = settingsTypeDescriptors[ settingsField.type ];

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

	if ( settingsField.type === 'info' ) {
		// DataForm's regular layout skips fields whose type resolves no Edit
		// control even when read-only, so info keeps the text type.
		field.type = 'text';
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

	if ( ! descriptor ) {
		// The renderer resolves registered type renderers before failing, so
		// unknown types keep Edit and render unset rather than a baked
		// fallback the adapter cannot decide on.
		warn( `Field type "${ settingsField.type }" is not supported.`, {
			field: settingsField,
		} );
		return field;
	}

	field.Edit = descriptor.edit;

	return field;
};

// Group descriptions and actions are HTML chrome that stays with the
// renderer; FormField.description only accepts a plain string.
const buildGroupFormField = ( group: SettingsUIGroup ): FormField => ( {
	id: group.id,
	label: group.title || undefined,
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
