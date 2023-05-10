export type ValidatorResponse = Promise< ValidationError >;

export type Validator< T > = ( initialValue?: T ) => ValidatorResponse;

export type ValidationContextProps< T > = {
	errors: ValidationErrors;
	registerValidator(
		validatorId: string,
		validator: Validator< T >
	): React.Ref< HTMLElement >;
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
	ref: React.Ref< HTMLElement >;
	error?: ValidationError;
	validate(): ValidatorResponse;
};
