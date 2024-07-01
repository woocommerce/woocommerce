// export type ValidatorResponse = Promise< {
// 	message?: ValidatorResponse;
// 	context?: string;
// } >;
export type ValidatorResponse = Promise< ValidationError >;

export type Validator< T > = (
	initialValue?: T,
	newData?: Record< string, unknown >
) => ValidatorResponse;
// export type Validator< T > = (
// 	initialValue?: T,
// 	newData?: Record< string, unknown >
// ) => ValidatorResponse | Promise< { message?: ValidatorResponse; context?: string } >;

export type ValidationContextProps< T > = {
	errors: { message?: ValidationErrors; context?: string };
	registerValidator(
		validatorId: string,
		validator: Validator< T >
	): React.Ref< HTMLElement >;
	unRegisterValidator( validatorId: string ): void;
	getFieldAndTabByValidatorId: ( validatorId: string ) => void;
	validateField(
		name: string,
		newData?: Record< string, unknown >,
		errorContext?: string
	): ValidatorResponse;
	validateAll( newData?: Partial< T > ): Promise< ValidationErrors >;
};

export type ValidationProviderProps = {
	postType: string;
	productId: number;
};

// export type ValidationError = string | undefined;
export type ValidationError =
	| { message?: string; context?: string }
	| undefined;
export type ValidationErrors = Record< string, ValidationError >;

export type ValidatorRegistration< T > = {
	name: string;
	ref: React.Ref< HTMLElement >;
	error?: ValidationError;
	validate( newData?: Partial< T > ): ValidatorResponse;
};
