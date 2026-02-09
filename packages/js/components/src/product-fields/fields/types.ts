export type BaseProductFieldProps< T > = {
	value: T;
	onChange: ( value: T ) => void;
	label: string;
	disabled?: boolean;
};
