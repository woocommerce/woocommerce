export type ValidatorResponse = Promise< ValidationError >;

export type Validator< T > = ( initialValue?: T ) => ValidatorResponse;

export type ValidationContextProps< T > = {
	errors: ValidationErrors;
	validators: Record< string, Validator< T > >;
	registerValidator( name: string, validator: Validator< T > ): void;
	validateField( name: string ): ValidatorResponse;
	validateAll(): Promise< ValidationErrors >;
};

export type ValidationProviderProps< T > = {
	initialValue?: T;
};

export type ValidationError = string | undefined;
export type ValidationErrors = Record< string, ValidationError >;

export type ValidatorRegistration = {
	name: string;
	error?: ValidationError;
	validate(): ValidatorResponse;
};
