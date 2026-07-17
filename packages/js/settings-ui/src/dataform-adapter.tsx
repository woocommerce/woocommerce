/**
 * External dependencies
 */
import { createElement } from '@wordpress/element';
import type {
	Field,
	FieldTypeName,
	Form,
	FormField,
	FormValidity,
	Rules,
} from '@wordpress/dataviews';

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

/**
 * A render section is either a run of consecutive groups rendered as one
 * DataForm with card-layout combined fields (the package-recommended
 * shape), or a single group that falls back to the shell-owned card
 * because the package card header has no slot for header actions.
 */
type DataFormRenderSection =
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

	return toSanitizedHtmlNode( description ) as unknown as string;
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

type ValueGetter = NonNullable< Field< SettingsValues >[ 'getValue' ] >;
type ValueSetter = NonNullable< Field< SettingsValues >[ 'setValue' ] >;

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

const defaultGetValue =
	( settingsField: SettingsUIField ): ValueGetter =>
	( { item } ) => {
		const value = item[ settingsField.id ];
		return typeof value === 'undefined' ? '' : value;
	};

const defaultSetValue =
	( settingsField: SettingsUIField ): ValueSetter =>
	( { value } ) => ( {
		[ settingsField.id ]:
			typeof value === 'undefined' ? null : ( value as SettingsValue ),
	} );

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
) => {
	return ( item: SettingsValues ) => {
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
		getValue: defaultGetValue( settingsField ),
		setValue: defaultSetValue( settingsField ),
		isVisible: createIsVisible( settingsField, options ),
		isValid:
			validationRules || customRule
				? {
						...validationRules,
						...( customRule ? { custom: customRule } : {} ),
				  }
				: undefined,
	};

	if ( settingsField.disabled ) {
		field.isDisabled = () => true;
	}

	if ( settingsField.options && settingsField.options.length > 0 ) {
		field.elements = settingsField.options;
	}

	if ( descriptor?.render ) {
		field.readOnly = true;
		field.render = descriptor.render( settingsField );
		return field;
	}

	let defaultEdit: Field< SettingsValues >[ 'Edit' ] | undefined;

	if ( ! descriptor ) {
		warn( `Field type "${ settingsField.type }" is not supported.`, {
			field: settingsField,
		} );
		field.type = 'text';
		// Unknown types can carry non-scalar values; present a string so
		// the package text control renders something meaningful.
		field.getValue = ( { item } ) =>
			toStringValue( item[ settingsField.id ] );
	} else {
		defaultEdit = descriptor.Edit;
	}

	const registeredComponent = resolveFieldComponent(
		settingsField,
		options.context
	);

	if ( registeredComponent ) {
		field.Edit = registeredComponent;
	} else if ( typeof defaultEdit !== 'undefined' ) {
		// String and config controls resolve inside DataForm; fields left
		// without an Edit use the package default for their type.
		field.Edit = defaultEdit;
	}

	return field;
};

// The card layout renders the combined field's description at the top of
// the card body, so group descriptions go through the package.
const getCardFormField = ( group: SettingsUIGroup ): FormField => ( {
	id: group.id,
	label: group.title,
	description: toDescriptionNode( group.description ),
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

		return predicate
			? runVisibilityPredicate(
					predicate,
					'group',
					group.id,
					values,
					options
			  )
			: true;
	};

	// The page derives the render sections and the validation form from
	// the same values object in one render pass, so a one-entry cache
	// halves the visibility evaluation per change.
	let lastVisibleGroupsValues: SettingsValues | undefined;
	let lastVisibleGroups: SettingsUIGroup[] = [];

	const getVisibleGroups = ( values: SettingsValues ) => {
		if ( values !== lastVisibleGroupsValues ) {
			lastVisibleGroupsValues = values;
			lastVisibleGroups = Object.values( schema.groups )
				.filter( ( group ) => isGroupVisible( group, values ) )
				.filter( ( group ) =>
					group.fields.some( ( field ) =>
						isFieldVisible( field.id, values )
					)
				);
		}

		return lastVisibleGroups;
	};

	let lastSectionsKey: string | undefined;
	let lastSections: DataFormRenderSection[] = [];

	const getRenderSections = (
		values: SettingsValues
	): DataFormRenderSection[] => {
		// Sections depend only on which groups are visible, so keep their
		// identity stable across value changes. Stable form objects let
		// DataForm's own normalization memos survive keystrokes.
		const visibleGroups = getVisibleGroups( values );
		const sectionsKey = visibleGroups
			.map( ( group ) => group.id )
			.join( '\n' );

		if ( sectionsKey === lastSectionsKey ) {
			return lastSections;
		}

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

		visibleGroups.forEach( ( group ) => {
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

		lastSectionsKey = sectionsKey;
		lastSections = sections;

		return sections;
	};

	// Only visible fields validate, so a hidden required field can never
	// block saving.
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

	return {
		fields,
		getRenderSections,
		getValidationForm,
	};
};
