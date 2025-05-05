export interface StateInputProps {
	className?: string;
	label?: string | undefined;
	id: string;
	autoComplete?: string | undefined;
	value: string;
	country: string;
	onChange: ( value: string ) => void;
	required?: boolean | undefined;
	errorMessage?: string | undefined;
	errorId?: string;
}

export type StateInputWithStatesProps = StateInputProps & {
	states: Record< string, Record< string, string > >;
};
