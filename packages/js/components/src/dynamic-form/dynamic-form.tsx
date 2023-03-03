/**
 * External dependencies
 */
import { createElement, useMemo } from '@wordpress/element';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

/**
 * Internal dependencies
 */
import { Form } from '../index';
import {
	TextField,
	PasswordField,
	CheckboxField,
	SelectField,
} from './field-types';

import { Field, FormInputProps } from './types';

type DynamicFormProps = {
	fields: Field[] | { [ key: string ]: Field };
	validate: ( values: Record< string, string > ) => Record< string, string >;
	isBusy?: boolean;
	onSubmit?: ( values: Record< string, string > ) => void;
	onChange?: (
		value: { name: string; value: unknown },
		values: Record< string, string >,
		result: boolean
	) => void;
	submitLabel?: string;
};

const fieldTypeMap = {
	text: TextField,
	password: PasswordField,
	checkbox: CheckboxField,
	select: SelectField,
	default: TextField,
};

const getInitialConfigValues = ( fields: Field[] ) =>
	fields.reduce(
		( data, field ) => ( {
			...data,
			[ field.id ]:
				field.type === 'checkbox' ? field.value === 'yes' : field.value,
		} ),
		{}
	);

export const DynamicForm: React.FC< DynamicFormProps > = ( {
	fields: baseFields = [],
	isBusy = false,
	onSubmit = () => {},
	onChange = () => {},
	validate = () => ( {} ),
	submitLabel = __( 'Proceed', 'woocommerce' ),
} ) => {
	// Support accepting fields in the format provided by the API (object), but transform to Array
	const fields =
		baseFields instanceof Array ? baseFields : Object.values( baseFields );

	const initialValues = useMemo(
		() => getInitialConfigValues( fields ),
		[ fields ]
	);

	return (
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		<Form< Record< string, any > >
			initialValues={ initialValues }
			onChange={ onChange }
			onSubmit={ onSubmit }
			validate={ validate }
		>
			{ ( {
				getInputProps,
				handleSubmit,
			}: {
				getInputProps: ( name: string ) => FormInputProps;
				handleSubmit: () => void;
			} ) => {
				return (
					<div className="woocommerce-component_dynamic-form">
						{ fields.map( ( field ) => {
							if (
								field.type &&
								! ( field.type in fieldTypeMap )
							) {
								/* eslint-disable no-console */
								console.warn(
									`Field type of ${ field.type } not current supported in DynamicForm component`
								);
								/* eslint-enable no-console */
								return null;
							}

							const Control =
								fieldTypeMap[ field.type || 'default' ];
							return (
								<Control
									key={ field.id }
									field={ field }
									{ ...getInputProps( field.id ) }
								/>
							);
						} ) }

						<Button
							isPrimary
							isBusy={ isBusy }
							onClick={ () => {
								handleSubmit();
							} }
						>
							{ submitLabel }
						</Button>
					</div>
				);
			} }
		</Form>
	);
};
