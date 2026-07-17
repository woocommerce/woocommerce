/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import type { Field, FieldTypeName, Form, Rules } from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { warn } from './diagnostics';
import { toSanitizedHtmlNode } from './html';
import {
	resolveFieldComponent,
	resolveFieldValidator,
	resolveFieldVisibilityPredicate,
	resolveGroupVisibilityPredicate,
} from './registry';
import type {
	SettingsFieldContext,
	SettingsUIField,
	SettingsUIGroup,
	SettingsUISchema,
	SettingsValue,
	SettingsValues,
	SettingsVisibilityPredicate,
} from './types';
import { areValuesEqual, toStringValue } from './values';

export type DataFormAdapterOptions = {
	schema: SettingsUISchema;
	context: SettingsFieldContext;
	initialValues: SettingsValues;
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

const toDescriptionNode = ( description?: string ) =>
	description && /<[^>]+>/.test( description )
		? toSanitizedHtmlNode( description )
		: description;

const createInfoRender = ( settingsField: SettingsUIField ) => {
	return function InfoField() {
		return (
			<div className="wc-settings-ui__info" id={ settingsField.id }>
				<strong>{ settingsField.label }</strong>
				{ settingsField.description
					? toSanitizedHtmlNode( settingsField.description )
					: null }
			</div>
		);
	};
};

/**
 * How a canonical Settings UI field type maps onto DataForm. Types without
 * an entry warn and render through the package text control.
 */
type SettingsTypeDescriptor = {
	type: FieldTypeName;
	Edit?: Field< SettingsValues >[ 'Edit' ];
	// Renders a read-only block instead of an edit control.
	render?: (
		settingsField: SettingsUIField
	) => Field< SettingsValues >[ 'render' ];
};

const settingsTypeDescriptors: Record< string, SettingsTypeDescriptor > = {
	checkbox: { type: 'boolean' },
	// Radio and select need explicit controls: fields with elements
	// default to the select control, so radio would lose its buttons and
	// an options-less select would fall back to a text input.
	radio: { type: 'text', Edit: 'radio' },
	select: { type: 'text', Edit: 'select' },
	textarea: { type: 'text', Edit: { control: 'textarea' } },
	number: { type: 'number' },
	integer: { type: 'integer' },
	tel: { type: 'telephone' },
	array: { type: 'array' },
	date: { type: 'date' },
	email: { type: 'email' },
	password: { type: 'password' },
	text: { type: 'text' },
	url: { type: 'url' },
	// The package has no time-only control (documented gap), so time
	// values edit as text and round-trip unchanged.
	time: { type: 'text' },
	'datetime-local': { type: 'datetime' },
	color: { type: 'color' },
	// The regular layout skips fields whose normalized Edit control is
	// null even when read-only, so info carries a control type the
	// read-only path never mounts.
	info: { type: 'text', render: createInfoRender },
};

// Predicates fail open: a broken visibility callback renders the field
// or group rather than hiding it.
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
		warn(
			`Visibility predicate for ${ kind } "${ id }" failed. Rendering it visible.`,
			{ error: predicateError, context: options.context }
		);
		return true;
	}
};

const createIsVisible = (
	settingsField: SettingsUIField,
	options: DataFormAdapterOptions
): Field< SettingsValues >[ 'isVisible' ] => {
	const predicate = resolveFieldVisibilityPredicate(
		settingsField.id,
		options.context
	);

	if ( predicate ) {
		return ( item ) =>
			runVisibilityPredicate(
				predicate,
				'field',
				settingsField.id,
				item,
				options
			);
	}

	const visibility = settingsField.visibility;
	return visibility
		? ( item ) =>
				valueMatchesVisibilityRule(
					item[ visibility.controller ],
					visibility.value
				)
		: undefined;
};

export const buildDataFormField = (
	settingsField: SettingsUIField,
	options: DataFormAdapterOptions
): Field< SettingsValues > => {
	const descriptor = settingsTypeDescriptors[ settingsField.type ];
	const type = descriptor?.type;
	const validationRules = settingsField.validation as
		| Rules< SettingsValues >
		| undefined;
	const validator = resolveFieldValidator( settingsField, options.context );
	const customRule: Rules< SettingsValues >[ 'custom' ] | undefined =
		validator
			? ( item, field ) =>
					validator( {
						value: field.getValue( { item } ) as
							| SettingsValue
							| undefined,
						values: item,
						field: settingsField,
						context: options.context,
					} )
			: undefined;

	const field: Field< SettingsValues > = {
		id: settingsField.id,
		label: settingsField.label,
		description: toDescriptionNode( settingsField.description ),
		placeholder: settingsField.placeholder,
		type,
		elements: settingsField.options,
		isDisabled: settingsField.disabled,
		isVisible: createIsVisible( settingsField, options ),
		isValid:
			validationRules || customRule
				? {
						...validationRules,
						...( customRule ? { custom: customRule } : {} ),
				  }
				: undefined,
	};

	const registeredComponent = resolveFieldComponent(
		settingsField,
		options.context
	);
	if ( registeredComponent ) {
		field.Edit = registeredComponent;
		return field;
	}

	if ( descriptor?.render ) {
		field.readOnly = true;
		field.render = descriptor.render( settingsField );
		return field;
	}

	if ( ! descriptor ) {
		warn( `Field type "${ settingsField.type }" is not supported.`, {
			field: settingsField,
		} );
		field.type = 'text';
		field.getValue = ( { item } ) =>
			toStringValue( item[ settingsField.id ] );
	} else {
		field.Edit = descriptor.Edit;
	}

	return field;
};

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
	const groupPredicates = new Map(
		Object.values( schema.groups ).map( ( group ) => [
			group.id,
			resolveGroupVisibilityPredicate( group.id, options.context ),
		] )
	);

	let lastValues: SettingsValues | undefined;
	let visibleGroups: SettingsUIGroup[] = [];
	const getVisibleGroups = ( values: SettingsValues ) => {
		if ( values !== lastValues ) {
			lastValues = values;
			visibleGroups = Object.values( schema.groups )
				.filter( ( group ) => {
					const predicate = groupPredicates.get( group.id );
					return predicate
						? runVisibilityPredicate(
								predicate,
								'group',
								group.id,
								values,
								options
						  )
						: true;
				} )
				.filter( ( group ) =>
					group.fields.some( ( field ) =>
						isFieldVisible( field.id, values )
					)
				);
		}

		return visibleGroups;
	};

	// Only visible, enabled fields validate.
	const getValidationForm = ( values: SettingsValues ): Form => ( {
		layout: { type: 'regular' },
		fields: getVisibleGroups( values ).map( ( group ) => ( {
			id: group.id,
			children: group.fields
				.filter( ( field ) => ! field.disabled )
				.filter( ( field ) => isFieldVisible( field.id, values ) )
				.map( ( field ) => field.id ),
		} ) ),
	} );

	return { fields, getVisibleGroups, getValidationForm };
};
