/**
 * External dependencies
 */
import type { ChangeEvent } from 'react';

export type FormErrors< Values > = {
	[ P in keyof Values ]?: FormErrors< Values[ P ] > | string;
};

export type FormProps< Values > = {
	/**
	 * Object of all initial errors to store in state.
	 */
	errors?: FormErrors< Values >;
	/**
	 * Object key:value pair list of all initial field values.
	 */
	initialValues?: Values;
	/**
	 * This prop helps determine whether or not a field has received focus
	 */
	touched?: Record< keyof Values, boolean >;
	/**
	 * Function to call when a form is submitted with valid fields.
	 *
	 * @deprecated
	 */
	onSubmitCallback?: ( values: Values ) => void;
	/**
	 * Function to call when a form is submitted with valid fields.
	 */
	onSubmit?: ( values: Values ) => void;
	/**
	 * Function to call when a value changes in the form.
	 *
	 * @deprecated
	 */
	onChangeCallback?: () => void;
	/**
	 * Function to call when a value changes in the form.
	 */
	onChange?: (
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		value: { name: string; value: any },
		values: Values,
		isValid: boolean
	) => void;
	/**
	 * Function to call when one or more values change in the form.
	 */
	onChanges?: (
		// eslint-disable-next-line @typescript-eslint/no-explicit-any
		changedValues: { name: string; value: any }[],
		values: Values,
		isValid: boolean
	) => void;
	/**
	 * A function that is passed a list of all values and
	 * should return an `errors` object with error response.
	 */
	validate?: ( values: Values ) => FormErrors< Values >;
};

export type FormRef< Values > = {
	resetForm: ( initialValues: Values ) => void;
};

export type InputProps< Values, Value > = {
	value: Value;
	checked: boolean;
	selected?: Value;
	onChange: (
		value: ChangeEvent< HTMLInputElement > | Values[ keyof Values ]
	) => void;
	onBlur: () => void;
	className: string | undefined;
	help?: React.ReactNode;
};

export type CheckboxProps< Values, Value > = Omit<
	InputProps< Values, Value >,
	'value' | 'selected'
>;

export type SelectControlProps< Values, Value > = Omit<
	InputProps< Values, Value >,
	'value'
> & {
	value: string | undefined;
};

export type ConsumerInputProps< Values > = {
	className?: string;
	onChange?: (
		value: ChangeEvent< HTMLInputElement > | Values[ keyof Values ]
	) => void;
	onBlur?: () => void;
	[ key: string ]: unknown;
	sanitize?: ( value: Values[ keyof Values ] ) => Values[ keyof Values ];
};

// eslint-disable-next-line @typescript-eslint/no-explicit-any
export type FormContextType< Values extends Record< string, any > = any > = {
	values: Values;
	errors: FormErrors< Values >;
	touched: { [ P in keyof Values ]?: boolean };
	isDirty: boolean;
	isValidForm: boolean;
	setTouched: React.Dispatch<
		React.SetStateAction< {
			[ P in keyof Values ]?: boolean | undefined;
		} >
	>;
	setValue: ( name: string, value: Values[ keyof Values ] ) => void;
	setValues: ( valuesToSet: Values ) => void;
	handleSubmit: () => Promise< void >;
	getCheckboxControlProps: < P extends keyof Values >(
		name: P,
		inputProps?: ConsumerInputProps< Values >
	) => CheckboxProps< Values, Values[ P ] >;
	getInputProps: < P extends keyof Values >(
		name: P,
		inputProps?: ConsumerInputProps< Values >
	) => InputProps< Values, Values[ P ] >;
	getSelectControlProps: < P extends keyof Values >(
		name: P,
		inputProps?: ConsumerInputProps< Values >
	) => SelectControlProps< Values, Values[ P ] >;
	resetForm: (
		newInitialValues?: Values,
		newTouchedFields?:
			| { [ P in keyof Values ]?: boolean | undefined }
			| undefined,
		newErrors?: FormErrors< Values >
	) => void;
};

export type PropsWithChildrenFunction< P, T > = P & {
	children?: React.ReactNode | ( ( props: T ) => React.ReactElement );
};
