export interface CountryInputProps {
	className?: string;
	label: string;
	id: string;
	autoComplete?: string;
	value: string;
	onChange: ( value: string ) => void;
	required?: boolean;
	errorMessage?: string;
	errorId: null | 'shipping-missing-country';
}

export type CountryInputWithCountriesProps = CountryInputProps & {
	countries: Record< string, string >;
};
