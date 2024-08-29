export type ValidatorResponse = Promise< ValidationError >;

export type Validator< T > = (
	initialValue?: T,
	newData?: Record< string, unknown >
) => ValidatorResponse;

export type ValidationContextProps< T > = {
	errors: ValidationErrors;
	registerValidator(
		validatorId: string,
		validator: Validator< T >
	): React.Ref< HTMLElement >;
	unRegisterValidator( validatorId: string ): void;
	getFieldByValidatorId: ( validatorId: string ) => Promise< HTMLElement >;
	validateField(
		name: string,
		newData?: Record< string, unknown >
	): ValidatorResponse;
	validateAll( newData?: Partial< T > ): Promise< ValidationErrors >;
};

export type ValidationProviderProps = {
	postType: string;
	productId: number;
};

export type ValidationError =
	| { message?: string; validatorId?: string }
	| undefined;
export type ValidationErrors = Record< string, ValidationError >;

export type ValidatorRegistration< T > = {
	name: string;
	ref: React.Ref< HTMLElement >;
	error?: ValidationError;
	validate( newData?: Partial< T > ): ValidatorResponse;
};
