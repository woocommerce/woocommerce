export type Field = {
	id: string;
	type: 'text' | 'password' | 'checkbox' | 'select';
	title: string;
	label: string;
	description?: string;
	default?: string;
	class?: string;
	css?: string;
	options?: Record< string, string >;
	tip?: string;
	value?: string;
	placeholder?: string;
};

export type FormInputProps = React.InputHTMLAttributes< HTMLInputElement > & {
	onChange: ( value: string | boolean ) => void;
};

export type ControlProps = FormInputProps & {
	field: Field;
};
