/**
 * External dependencies
 */
import type {
	Field,
	Form,
	FormValidity,
	NormalizedField,
} from '@wordpress/dataviews';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import {
	createDateTimeEdit,
	createInfoEdit,
	createSettingsComponentEdit,
	createUnsupportedEdit,
} from './dataform-controls';
import { warn } from './diagnostics';
import { sanitizeSettingsHtml } from './html';
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
} from './types';

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
			reasons: ( 'actions' | 'description' )[];
	  };

type DataFormCustomValidator = NonNullable<
	NonNullable< Field< SettingsValues >[ 'isValid' ] >[ 'custom' ]
>;

type AdapterOptions = {
	schema: SettingsUISchema;
	context: SettingsFieldContext;
	initialValues: SettingsValues;
};

const SUPPORTED_TYPES = new Set( [
	'array',
	'checkbox',
	'date',
	'email',
	'number',
	'password',
	'radio',
	'select',
	'tel',
	'text',
	'textarea',
	'url',
] );

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

const isChecked = ( value: SettingsValue | undefined ) =>
	value === true || value === 'yes' || value === '1' || value === 1;

const valueMatchesVisibilityRule = (
	value: SettingsValue,
	expected: SettingsValue | SettingsValue[] | undefined
) => {
	const expectedValues = Array.isArray( expected )
		? expected
		: [ expected ?? true ];

	return expectedValues.some( ( expectedValue ) => {
		if ( typeof expectedValue === 'boolean' ) {
			return isChecked( value ) === expectedValue;
		}

		return areValuesEqual( value, expectedValue );
	} );
};

const getFieldVisibility = ( {
	field,
	context,
	initialValues,
	schema,
}: AdapterOptions & { field: SettingsUIField } ) => {
	const predicate = resolveFieldVisibilityPredicate( field.id, context );

	if ( predicate ) {
		return ( values: SettingsValues ) => {
			try {
				return predicate( { values, initialValues, context, schema } );
			} catch ( predicateError ) {
				warn(
					`Visibility predicate for field "${ field.id }" failed. Rendering it visible.`,
					{ error: predicateError, context }
				);
				return true;
			}
		};
	}

	const visibility = field.visibility;
	if ( visibility ) {
		return ( values: SettingsValues ) =>
			valueMatchesVisibilityRule(
				values[ visibility.controller ],
				visibility.value
			);
	}

	return undefined;
};

const isGroupVisible = ( {
	group,
	values,
	context,
	initialValues,
	schema,
}: AdapterOptions & { group: SettingsUIGroup; values: SettingsValues } ) => {
	const predicate = resolveGroupVisibilityPredicate( group.id, context );
	if ( ! predicate ) {
		return true;
	}

	try {
		return predicate( { values, initialValues, context, schema } );
	} catch ( predicateError ) {
		warn(
			`Visibility predicate for group "${ group.id }" failed. Rendering it visible.`,
			{ error: predicateError, context }
		);
		return true;
	}
};

const getDescription = ( description?: string ) => {
	if ( ! description ) {
		return undefined;
	}

	if ( ! /<[^>]+>/.test( description ) ) {
		return description;
	}

	// DataForm types descriptions as strings, while the existing public schema
	// permits sanitized HTML. WordPress controls accept this React node as help.
	return createElement( 'span', {
		dangerouslySetInnerHTML: {
			__html: sanitizeSettingsHtml( description ),
		},
	} ) as unknown as string;
};

const getValidation = ( {
	field,
	context,
	initialValues,
	schema,
}: AdapterOptions & {
	field: SettingsUIField;
} ): Field< SettingsValues >[ 'isValid' ] => {
	const validation = field.validation;
	if ( ! validation ) {
		return undefined;
	}

	const customValidator = validation.validator
		? resolveFieldValidator( validation.validator, context )
		: undefined;
	const custom = customValidator
		? ( ( (
				values: SettingsValues,
				dataFormField: NormalizedField< SettingsValues >
		  ) =>
				customValidator( {
					field,
					value: dataFormField.getValue( {
						item: values,
					} ) as SettingsValue,
					values,
					initialValues,
					context,
					schema,
				} ) ) as DataFormCustomValidator )
		: undefined;

	return {
		...( typeof validation.required === 'boolean'
			? { required: validation.required }
			: {} ),
		...( typeof validation.elements === 'boolean'
			? { elements: validation.elements }
			: {} ),
		...( custom ? { custom } : {} ),
	};
};

const getDataFormTypeAndEdit = ( field: SettingsUIField ) => {
	switch ( field.type ) {
		case 'checkbox':
			return { type: 'boolean' as const, Edit: 'checkbox' as const };
		case 'radio':
			return { type: 'text' as const, Edit: 'radio' as const };
		case 'select':
			return { type: 'text' as const, Edit: 'select' as const };
		case 'textarea':
			return {
				type: 'text' as const,
				Edit: { control: 'textarea' as const },
			};
		case 'tel':
			return { type: 'telephone' as const, Edit: 'telephone' as const };
		case 'array':
			return { type: 'array' as const, Edit: 'array' as const };
		case 'number':
			return { type: 'number' as const, Edit: 'number' as const };
		case 'date':
		case 'email':
		case 'password':
		case 'text':
		case 'url':
			return { type: field.type, Edit: field.type };
		default:
			return { type: 'text' as const };
	}
};

const getDataFormField = ( {
	field,
	context,
	initialValues,
	schema,
}: AdapterOptions & { field: SettingsUIField } ): {
	field: Field< SettingsValues >;
	unsupported: boolean;
} => {
	const registeredComponent = resolveFieldComponent( field, context );
	const dataFormType = getDataFormTypeAndEdit( field );
	let Edit: Field< SettingsValues >[ 'Edit' ] = dataFormType.Edit;
	let unsupported = false;

	if ( registeredComponent ) {
		Edit = createSettingsComponentEdit( {
			component: registeredComponent,
			settingsField: field,
			initialValues,
			context,
		} );
	} else if ( field.type === 'info' ) {
		Edit = createInfoEdit( field );
	} else if ( field.type === 'time' || field.type === 'datetime-local' ) {
		Edit = createDateTimeEdit( field, field.type );
	} else if ( ! SUPPORTED_TYPES.has( field.type ) ) {
		unsupported = true;
		Edit = createUnsupportedEdit( field );
		warn( `Field type "${ field.type }" is not supported.`, { field } );
	}

	return {
		field: {
			id: field.id,
			label: field.label,
			type: dataFormType.type,
			description: getDescription( field.description ),
			placeholder: field.placeholder,
			elements: field.options,
			Edit,
			isValid: getValidation( {
				field,
				context,
				initialValues,
				schema,
			} ),
			isVisible: getFieldVisibility( {
				field,
				context,
				initialValues,
				schema,
			} ),
			...( field.type === 'checkbox'
				? {
						getValue: ( { item }: { item: SettingsValues } ) =>
							isChecked( item[ field.id ] ),
						setValue: ( { value }: { value: unknown } ) => ( {
							[ field.id ]: Boolean( value ),
						} ),
				  }
				: {} ),
		},
		unsupported,
	};
};

const getFallbackReasons = ( group: SettingsUIGroup ) => [
	...( group.description ? ( [ 'description' ] as const ) : [] ),
	...( group.actions?.length ? ( [ 'actions' ] as const ) : [] ),
];

const getCardFormField = ( group: SettingsUIGroup ) => ( {
	id: group.id,
	...( group.title ? { label: group.title } : {} ),
	layout: group.title
		? {
				type: 'card' as const,
				withHeader: true as const,
				isCollapsible: false,
		  }
		: {
				type: 'card' as const,
				withHeader: false as const,
				isCollapsible: false as const,
		  },
	children: group.fields.map( ( field ) => field.id ),
} );

export const getGroupValidity = (
	validity: FormValidity,
	groupId: string
): FormValidity => validity?.[ groupId ]?.children;

export const createDataFormAdapter = ( options: AdapterOptions ) => {
	const { schema } = options;
	const adaptedFields = Object.values( schema.groups ).flatMap( ( group ) =>
		group.fields.map( ( field ) =>
			getDataFormField( { ...options, field } )
		)
	);
	const fields = adaptedFields.map( ( adaptedField ) => adaptedField.field );
	const unsupportedFields = Object.values( schema.groups ).flatMap(
		( group ) =>
			group.fields.filter( ( field ) =>
				adaptedFields.some(
					( adaptedField ) =>
						adaptedField.field.id === field.id &&
						adaptedField.unsupported
				)
			)
	);
	const fieldsById = new Map(
		fields.map( ( field ) => [ field.id, field ] )
	);

	const getVisibleGroups = ( values: SettingsValues ) =>
		Object.values( schema.groups )
			.filter( ( group ) =>
				isGroupVisible( { ...options, group, values } )
			)
			.filter( ( group ) =>
				group.fields.some( ( field ) => {
					const dataFormField = fieldsById.get( field.id );
					return dataFormField?.isVisible?.( values ) !== false;
				} )
			);

	const getRenderSections = (
		values: SettingsValues
	): DataFormRenderSection[] => {
		const sections: DataFormRenderSection[] = [];
		let pendingCardFields: ReturnType< typeof getCardFormField >[] = [];
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
			const reasons = getFallbackReasons( group );
			if ( reasons.length === 0 ) {
				pendingCardFields.push( getCardFormField( group ) );
				return;
			}

			flushDataForm();
			sections.push( {
				type: 'fallback',
				key: `fallback-${ group.id }`,
				group,
				reasons,
				form: {
					layout: { type: 'regular' },
					fields: group.fields.map( ( field ) => field.id ),
				},
			} );
		} );
		flushDataForm();

		return sections;
	};

	const getValidationForm = ( values: SettingsValues ): Form => ( {
		layout: { type: 'regular' },
		fields: getVisibleGroups( values ).map( ( group ) => ( {
			id: group.id,
			children: group.fields.map( ( field ) => field.id ),
		} ) ),
	} );

	return {
		fields,
		unsupportedFields,
		getRenderSections,
		getValidationForm,
	};
};
