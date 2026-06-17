/**
 * External dependencies
 */
import { DataForm } from '@wordpress/dataviews';
import { createElement, useMemo } from '@wordpress/element';
import type {
	DataFormControlProps,
	Field as DataFormField,
	Form as DataFormSchema,
} from '@wordpress/dataviews';

/**
 * Internal dependencies
 */
import { NativeSettingsField } from './native-fields';
import { resolveFieldComponent } from './registry';
import type {
	SettingsFieldComponent,
	SettingsFieldContext,
	SettingsUIField,
	SettingsValue,
	SettingsValues,
} from './types';

type SettingsDataFormProps = {
	fields: SettingsUIField[];
	values: SettingsValues;
	initialValues: SettingsValues;
	context: SettingsFieldContext;
	setValue: ( fieldId: string, value: SettingsValue ) => void;
	setValues: ( values: Partial< SettingsValues > ) => void;
};

type SettingsDataFormControlProps = DataFormControlProps< SettingsValues > & {
	FieldComponent: SettingsFieldComponent;
	fieldSchema: SettingsUIField;
	initialValues: SettingsValues;
	context: SettingsFieldContext;
	setValue: ( fieldId: string, value: SettingsValue ) => void;
	setValues: ( values: Partial< SettingsValues > ) => void;
};

const getFieldTypeClassName = ( type: string ) =>
	`wc-settings-ui__field--${ type.replace( /[^a-z0-9_-]/gi, '-' ) }`;

const SettingsDataFormControl = ( {
	FieldComponent,
	fieldSchema,
	data,
	onChange,
	initialValues,
	context,
	setValue,
	setValues,
}: SettingsDataFormControlProps ) => {
	const value = data[ fieldSchema.id ];

	return (
		<div
			className={ [
				'wc-settings-ui__field',
				getFieldTypeClassName( fieldSchema.type ),
			].join( ' ' ) }
		>
			<FieldComponent
				field={ fieldSchema }
				value={ value }
				context={ context }
				values={ data }
				initialValues={ initialValues }
				setValue={ setValue }
				setValues={ setValues }
				onChange={ ( nextValue ) =>
					onChange( { [ fieldSchema.id ]: nextValue } )
				}
			/>
		</div>
	);
};

export const SettingsDataForm = ( {
	fields,
	values,
	initialValues,
	context,
	setValue,
	setValues,
}: SettingsDataFormProps ) => {
	const dataFormFields = useMemo(
		() =>
			fields.map( ( fieldSchema ): DataFormField< SettingsValues > => {
				const FieldComponent =
					resolveFieldComponent( fieldSchema, context ) ||
					NativeSettingsField;

				const Edit = (
					props: DataFormControlProps< SettingsValues >
				) => (
					<SettingsDataFormControl
						{ ...props }
						FieldComponent={ FieldComponent }
						fieldSchema={ fieldSchema }
						initialValues={ initialValues }
						context={ context }
						setValue={ setValue }
						setValues={ setValues }
					/>
				);

				return {
					id: fieldSchema.id,
					label: fieldSchema.label,
					description: fieldSchema.description,
					placeholder: fieldSchema.placeholder,
					Edit,
					enableHiding: false,
					enableSorting: false,
					getValue: ( { item } ) => item[ fieldSchema.id ],
				};
			} ),
		[ context, fields, initialValues, setValue, setValues ]
	);

	const form = useMemo< DataFormSchema >(
		() => ( {
			type: 'regular',
			fields: fields.map( ( field ) => field.id ),
		} ),
		[ fields ]
	);

	return (
		<DataForm
			data={ values }
			fields={ dataFormFields }
			form={ form }
			onChange={ setValues }
		/>
	);
};
