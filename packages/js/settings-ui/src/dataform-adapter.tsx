/**
 * External dependencies
 */
import { createElement, RawHTML } from '@wordpress/element';
import type {
	Field,
	FieldTypeName,
	Form,
	FormField,
} from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { error, warn } from './diagnostics';
import { createSettingsHelpElement, sanitizeSettingsHtml } from './html';
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
		return predicate( {
			values,
			initialValues: options.initialValues,
			context: options.context,
			schema: options.schema,
		} );
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

// DataForm renders the label for read-only fields itself, so the info
// renderer paints only the sanitized description.
const createInfoRender = ( settingsField: SettingsUIField ) => {
	return function InfoSettingsField() {
		return settingsField.description ? (
			<RawHTML className="wc-settings-ui__info">
				{ sanitizeSettingsHtml( settingsField.description ) }
			</RawHTML>
		) : null;
	};
};

// Classic settings disable fields through custom_attributes with HTML
// presence semantics, so any defined value except boolean false disables.
const isFieldDisabled = ( settingsField: SettingsUIField ) => {
	if ( settingsField.disabled ) {
		return true;
	}

	const disabledAttribute = settingsField.customAttributes?.disabled;
	return (
		typeof disabledAttribute !== 'undefined' && disabledAttribute !== false
	);
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
		isVisible: createIsVisible( settingsField, options ),
		isDisabled: isFieldDisabled( settingsField ),
	};

	if ( settingsField.type === 'info' ) {
		// DataForm's regular layout skips fields whose type resolves no Edit
		// control even when read-only, so info keeps the text type.
		field.type = 'text';
		field.readOnly = true;
		field.render = createInfoRender( settingsField );
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
