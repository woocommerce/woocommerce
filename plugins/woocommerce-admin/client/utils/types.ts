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

// Type guard function to check if children is a function, while still type narrowing correctly.
export function isCallable(
	children: unknown
): children is ( props: unknown ) => React.ReactNode {
	return typeof children === 'function';
}
