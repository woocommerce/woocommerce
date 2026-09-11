/**
 * External dependencies
 */
import type {
	Field,
	FieldTypeName,
	Form,
	FormField,
	Rules,
} from '@wordpress/dataviews';
import { createElement } from '@wordpress/element';
import type { ReactNode } from 'react';
import { __ } from '@wordpress/i18n';

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
	SettingsRelativeDateValue,
	SettingsUIField,
	SettingsUIGroup,
	SettingsUISchema,
	SettingsValues,
	SettingsVisibilityPredicate,
} from './types';
import { isRelativeDateValue, valueMatchesVisibilityRule } from './values';

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
	integer: { type: 'integer' },
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

const isFieldStaticallyDisabled = ( settingsField: SettingsUIField ) =>
	Boolean( settingsField.disabled ) ||
	isAttributeSet( settingsField.customAttributes?.disabled );

const getDisabledControllers = ( settingsField: SettingsUIField ) => {
	const controllers = settingsField.disabledWhenAllUnchecked;

	return Array.isArray( controllers )
		? controllers.filter( ( controller ) => Boolean( controller ) )
		: [];
};

export const isSettingsFieldDisabled = (
	settingsField: SettingsUIField,
	values: SettingsValues
) => {
	if ( isFieldStaticallyDisabled( settingsField ) ) {
		return true;
	}

	const controllers = getDisabledControllers( settingsField );
	return (
		controllers.length > 0 &&
		controllers.every( ( controller ) => values[ controller ] !== true )
	);
};

const buildIsDisabled = (
	settingsField: SettingsUIField
): Field< SettingsValues >[ 'isDisabled' ] => {
	if ( getDisabledControllers( settingsField ).length === 0 ) {
		return isFieldStaticallyDisabled( settingsField );
	}

	return ( { item } ) => isSettingsFieldDisabled( settingsField, item );
};

const getFieldDescription = ( settingsField: SettingsUIField ) => {
	const disabledTooltip =
		settingsField.customAttributes?.[ 'disabled-tooltip' ];
	const descriptions = [
		settingsField.description,
		typeof disabledTooltip === 'string' ? disabledTooltip : undefined,
	].filter( Boolean );

	return descriptions.length > 0 ? descriptions.join( '<br />' ) : undefined;
};

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

	if ( type === 'number' || type === 'integer' ) {
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
// The number and unit are one stored value, so both controls project onto the
// same field id through getValue/setValue. DataForm renders them with its own
// controls, and the row layout puts them on one line.
const RELATIVE_DATE_FALLBACK: SettingsRelativeDateValue = {
	number: '',
	unit: 'days',
};

const readRelativeDate = (
	item: SettingsValues,
	fieldId: string
): SettingsRelativeDateValue => {
	const value = item[ fieldId ];
	return isRelativeDateValue( value ) ? value : RELATIVE_DATE_FALLBACK;
};

const relativeDateChildId = (
	fieldId: string,
	part: 'number' | 'unit' | 'description'
) => `${ fieldId }__${ part }`;

// The stored number is '' when the setting is unset, which DataForm's integer
// control expresses as undefined.
const buildRelativeDateFields = (
	settingsField: SettingsUIField,
	options: DataFormAdapterOptions
): Field< SettingsValues >[] => {
	const isVisible = createIsVisible( settingsField, options );
	const isDisabled = buildIsDisabled( settingsField );
	const fieldId = settingsField.id;

	return [
		{
			id: relativeDateChildId( fieldId, 'number' ),
			label: __( 'Number', 'woocommerce' ),
			placeholder: settingsField.placeholder,
			type: 'integer',
			isVisible,
			isDisabled,
			isValid: { min: 1 },
			getValue: ( { item }: { item: SettingsValues } ) => {
				const { number } = readRelativeDate( item, fieldId );
				return number === '' ? undefined : number;
			},
			setValue: ( {
				item,
				value,
			}: {
				item: SettingsValues;
				value: unknown;
			} ) => ( {
				[ fieldId ]: {
					...readRelativeDate( item, fieldId ),
					number:
						value === null ||
						value === undefined ||
						value === '' ||
						Number.isNaN( Number( value ) )
							? ''
							: Number( value ),
				},
			} ),
		},
		{
			id: relativeDateChildId( fieldId, 'unit' ),
			label: __( 'Unit', 'woocommerce' ),
			type: 'text',
			Edit: 'select',
			elements: settingsField.options,
			isVisible,
			isDisabled,
			getValue: ( { item }: { item: SettingsValues } ) =>
				readRelativeDate( item, fieldId ).unit,
			setValue: ( {
				item,
				value,
			}: {
				item: SettingsValues;
				value: unknown;
			} ) => ( {
				[ fieldId ]: {
					...readRelativeDate( item, fieldId ),
					unit: value as SettingsRelativeDateValue[ 'unit' ],
				},
			} ),
		},
		// No container layout renders a description, so it becomes a read-only
		// sibling below the row, where the classic page puts it too.
		...( settingsField.description
			? [
					{
						id: relativeDateChildId( fieldId, 'description' ),
						label: '',
						description: createSettingsHelpElement(
							settingsField.description
						),
						type: 'text' as const,
						readOnly: true,
						isVisible,
						render: ( {
							field,
						}: {
							field: { description?: ReactNode };
						} ) => renderFieldDescription( field.description ),
					} as unknown as Field< SettingsValues >,
			  ]
			: [] ),
	];
};

const buildValidationRules = (
	settingsField: SettingsUIField,
	descriptor: SettingsTypeDescriptor | undefined
): Rules< SettingsValues > => {
	const attributes = settingsField.customAttributes ?? {};
	const validation = settingsField.validation ?? {};
	const rules: Rules< SettingsValues > = {};

	if ( isAttributeSet( attributes.required ) ) {
		rules.required = true;
	}

	const min = toRangeConstraint(
		validation.min ?? attributes.min,
		descriptor?.type
	);
	if ( typeof min !== 'undefined' ) {
		rules.min = min;
	}

	const max = toRangeConstraint(
		validation.max ?? attributes.max,
		descriptor?.type
	);
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

// The control throws when DataForm renders it, so a hidden field with a
// broken config stays harmless while a visible one still fails closed.
const createFailingControl =
	( message: string ): Field< SettingsValues >[ 'Edit' ] =>
	() => {
		throw new Error( message );
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
		description: createSettingsHelpElement(
			getFieldDescription( settingsField )
		),
		placeholder: settingsField.placeholder,
		type: descriptor?.type,
		elements: settingsField.options,
		isValid: buildValidationRules( settingsField, descriptor ),
		isVisible: createIsVisible( settingsField, options ),
		isDisabled: buildIsDisabled( settingsField ),
	};

	if ( registeredComponent ) {
		// A registered control accepts a frozen subset of the DataForm control
		// props, so the wider package props remain assignable to it.
		field.Edit = registeredComponent as Field< SettingsValues >[ 'Edit' ];
		return field;
	}

	// A field declaring a component requires that custom control. Failing
	// closed beats silently rendering a built-in control in its place.
	if ( settingsField.component ) {
		field.Edit = createFailingControl(
			`Component "${ settingsField.component }" is not registered.`
		);
		return field;
	}

	if ( settingsField.type === 'info' ) {
		field.readOnly = true;
		// The description is already a sanitized element, and DataForm paints
		// the label for a read-only field, so info reuses it as its body.
		field.render = ( { field: normalizedField } ) =>
			renderFieldDescription( normalizedField.description );
		return field;
	}

	// Registered renderers resolve above, so reaching here means nothing can
	// draw the field. Failing closed beats dropping it beside a live Save
	// button, and matches the page this renderer replaces.
	if ( ! descriptor ) {
		field.Edit = createFailingControl(
			`Field type "${ settingsField.type }" is not supported.`
		);
		return field;
	}

	if ( descriptor.edit ) {
		field.Edit = descriptor.edit;
	}

	return field;
};

// DataForm paints a description as muted help text for its own controls, and
// muted card text for a card container. A description we render ourselves gets
// neither, so it carries the class that matches them.
const renderFieldDescription = ( description?: ReactNode ) =>
	description
		? createElement(
				'div',
				{ className: 'wc-settings-ui__field-description' },
				description
		  )
		: null;

// FormField.description only accepts a plain string, so a group description
// keeps its text and loses its markup until DataForm accepts an element.
const buildFormChild = (
	field: SettingsUIField
): Array< FormField | string > => {
	if ( field.type !== 'relative_date_selector' ) {
		return [ field.id ];
	}

	// The number stays narrow so the pair reads as one control rather than two
	// equal columns.
	return [
		{
			id: field.id,
			label: field.label,
			layout: {
				type: 'row',
				alignment: 'start',
				styles: {
					[ relativeDateChildId( field.id, 'number' ) ]: {
						flex: '0 1 8rem',
					},
					[ relativeDateChildId( field.id, 'unit' ) ]: {
						flex: '0 1 10rem',
					},
				},
			},
			children: [
				relativeDateChildId( field.id, 'number' ),
				relativeDateChildId( field.id, 'unit' ),
			].map( ( id ) => ( {
				id,
				layout: {
					type: 'regular' as const,
					labelPosition: 'none' as const,
				},
			} ) ),
		},
		// DataForm falls back to the field id when a label is empty, so the
		// description renders with its label position turned off.
		...( field.description
			? [
					{
						id: relativeDateChildId( field.id, 'description' ),
						layout: {
							type: 'regular' as const,
							labelPosition: 'none' as const,
						},
					},
			  ]
			: [] ),
	];
};

const buildGroupFormField = (
	group: SettingsUIGroup,
	visibleFields: SettingsUIField[]
): FormField => ( {
	id: group.id,
	label: group.title || undefined,
	description: toPlainText( group.description ),
	layout: group.title
		? { type: 'card', isCollapsible: false }
		: { type: 'card', withHeader: false },
	children: visibleFields.flatMap( buildFormChild ),
} );

export const createDataFormAdapter = (
	options: DataFormAdapterOptions
): DataFormAdapter => {
	const groups = Object.values( options.schema.groups );
	const fields = groups.flatMap( ( group ) =>
		group.fields.flatMap( ( field ) =>
			field.type === 'relative_date_selector'
				? buildRelativeDateFields( field, options )
				: [ buildDataFormField( field, options ) ]
		)
	);
	// A relative date field builds two DataForm fields, so visibility is keyed
	// by the schema field id rather than by the DataForm field id.
	const visibilityBySettingsFieldId = new Map(
		groups.flatMap( ( group ) =>
			group.fields.map(
				( field ) =>
					[ field.id, createIsVisible( field, options ) ] as const
			)
		)
	);

	// A field without an isVisible callback is shown, matching DataForm.
	const isFieldVisible = ( fieldId: string, values: SettingsValues ) =>
		Boolean(
			visibilityBySettingsFieldId.get( fieldId )?.( values ) ?? true
		);

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

	// DataForm only consults isVisible for a form field without children, so a
	// hidden relative date row is dropped here instead. Leaving it in would
	// render its label above two hidden controls.
	const getForm = ( values: SettingsValues ): Form => ( {
		fields: groups
			.filter( ( group ) => isGroupVisible( group, values ) )
			.map( ( group ) =>
				buildGroupFormField(
					group,
					group.fields.filter(
						( field ) =>
							field.type !== 'relative_date_selector' ||
							isFieldVisible( field.id, values )
					)
				)
			),
	} );

	return { fields, getForm };
};
