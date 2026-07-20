/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import type {
	DataFormControlProps,
	Field,
	FieldType,
	Form,
} from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { warn } from './diagnostics';
import { toSanitizedHtmlNode, toSanitizedText } from './html';
import {
	resolveEditControlRegistration,
	resolveFieldVisibilityPredicate,
	resolveGroupVisibilityPredicate,
} from './registry';
import {
	createUIFieldEdit,
	flattenDataFormValidity,
} from './ui-field-controls';
import type { UIFieldControl } from './ui-field-controls';
import type {
	SettingsEditControl,
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

/** How a canonical Settings UI field type maps onto DataForm. */
type SettingsTypeDescriptor = {
	type: FieldType;
	Edit?: Field< SettingsValues >[ 'Edit' ];
	uiControl?: UIFieldControl;
	render?: (
		settingsField: SettingsUIField
	) => Field< SettingsValues >[ 'render' ];
};

const settingsTypeDescriptors: Record< string, SettingsTypeDescriptor > = {
	checkbox: { type: 'boolean' },
	radio: { type: 'text', Edit: 'radio' },
	select: { type: 'text', uiControl: 'select' },
	textarea: { type: 'text', uiControl: 'textarea' },
	number: { type: 'number' },
	integer: { type: 'integer' },
	tel: { type: 'telephone', uiControl: 'tel' },
	array: { type: 'array' },
	date: { type: 'date', uiControl: 'date' },
	email: { type: 'email', uiControl: 'email' },
	password: { type: 'password', uiControl: 'password' },
	text: { type: 'text', uiControl: 'text' },
	url: { type: 'url', uiControl: 'url' },
	time: { type: 'text', uiControl: 'time' },
	'datetime-local': {
		type: 'datetime',
		uiControl: 'datetime-local',
	},
	color: { type: 'color' },
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

const createRegisteredEdit = (
	settingsField: SettingsUIField,
	Registered: SettingsEditControl
) => {
	return function RegisteredFieldEdit( {
		data,
		field,
		onChange,
		hideLabelFromVision,
		validity,
	}: DataFormControlProps< SettingsValues > ) {
		return (
			<Registered
				data={ data }
				field={ {
					id: field.id,
					label: field.label,
					description: field.description,
					placeholder: field.placeholder,
					elements: field.elements,
					getValue: ( { item } ) => field.getValue( { item } ),
				} }
				onChange={ ( values ) => onChange( values ) }
				hideLabelFromVision={ Boolean( hideLabelFromVision ) }
				disabled={ Boolean( settingsField.disabled ) }
				validity={ flattenDataFormValidity( validity ) }
			/>
		);
	};
};

export const buildDataFormField = (
	settingsField: SettingsUIField,
	options: DataFormAdapterOptions
): Field< SettingsValues > => {
	const descriptor = settingsTypeDescriptors[ settingsField.type ];
	const registeredComponent = resolveEditControlRegistration(
		settingsField,
		options.context
	);
	const field: Field< SettingsValues > = {
		id: settingsField.id,
		label: settingsField.label,
		description: toSanitizedText( settingsField.description ),
		placeholder: settingsField.placeholder,
		type: descriptor?.type || 'text',
		elements: settingsField.options,
		isVisible: createIsVisible( settingsField, options ),
	};

	if ( registeredComponent?.isLegacy && settingsField.disabled ) {
		const scopeKey = `${ options.context.page }::${
			options.context.section || 'default'
		}`;
		warn(
			`Legacy component for disabled field "${ settingsField.id }" in scope "${ scopeKey }" was not mounted. The field is read-only until the component migrates to the modern disabled contract.`,
			{ field: settingsField, context: options.context },
			`disabled-legacy-component:${ scopeKey }:${ settingsField.id }`
		);
		field.readOnly = true;
		return field;
	}

	if ( registeredComponent ) {
		// DataForm sees its own control props at this boundary. The wrapper
		// converts them to the stable Woo-owned extension contract.
		field.Edit = createRegisteredEdit(
			settingsField,
			registeredComponent.component
		) as Field< SettingsValues >[ 'Edit' ];
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
		field.getValue = ( { item } ) =>
			toStringValue( item[ settingsField.id ] );
		field.Edit = createUIFieldEdit( settingsField, 'text' );
		return field;
	}

	if ( descriptor.uiControl ) {
		field.Edit = createUIFieldEdit( settingsField, descriptor.uiControl );
		return field;
	}

	field.Edit = descriptor.Edit;

	// Delete this read-only bridge when the WordPress runtime DataForm supports
	// field-level disabled state. Registered controls instead receive `disabled`.
	if ( settingsField.disabled ) {
		field.readOnly = true;
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

	const getValidationFields = ( values: SettingsValues ) =>
		getVisibleGroups( values ).flatMap( ( group ) =>
			group.fields
				.filter( ( field ) => ! field.disabled )
				.filter( ( field ) => isFieldVisible( field.id, values ) )
		);

	const getValidationForm = ( values: SettingsValues ): Form => ( {
		layout: { type: 'regular' },
		fields: getVisibleGroups( values ).map( ( group ) => ( {
			id: group.id,
			children: getValidationFields( values )
				.filter( ( field ) =>
					group.fields.some(
						( candidate ) => candidate.id === field.id
					)
				)
				.map( ( field ) => field.id ),
		} ) ),
	} );

	return {
		fields,
		getVisibleGroups,
		getValidationFields,
		getValidationForm,
	};
};
