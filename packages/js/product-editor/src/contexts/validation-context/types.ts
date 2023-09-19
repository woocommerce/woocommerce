export type ValidatorResponse = Promise< ValidationError >;

export type Validator< T > = (
	initialValue?: T,
	newData?: Partial< T >
) => ValidatorResponse;

export type ValidationContextProps< T > = {
	errors: ValidationErrors;
	registerValidator(
		validatorId: string,
		validator: Validator< T >
	): React.Ref< HTMLElement >;
	validateField( name: string ): ValidatorResponse;
	validateAll( newData?: Partial< T > ): Promise< ValidationErrors >;
};

export type ValidationProviderProps< T > = {
	initialValue?: T;
};

export type ValidationError = string | undefined;
export type ValidationErrors = Record< string, ValidationError >;

export type ValidatorRegistration< T > = {
	name: string;
	ref: React.Ref< HTMLElement >;
	error?: ValidationError;
	validate( newData?: Partial< T > ): ValidatorResponse;
};
