export type FormValue = HTMLInputElement[ 'value' ];

// TODO: move to packages/components/Form when we convert From to TS.
export type FormInputProps = {
	value: FormValue;
	checked: boolean;
	selected: FormValue;
	onChange: ( value: FormValue ) => void;
	onBlur: () => void;
	className: string;
	help: string | null;
};
