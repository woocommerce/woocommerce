export type ValidationContextError = {
	message: string;
	hidden: boolean;
};

export type ValidationData = {
	hasValidationErrors: boolean;
	getValidationError: ( validationErrorId: string ) => ValidationContextError;
	clearValidationError: ( validationErrorId: string ) => void;
	hideValidationError: ( validationErrorId: string ) => void;
	setValidationErrors: (
		errors: Record< string, ValidationContextError >
	) => void;
};
