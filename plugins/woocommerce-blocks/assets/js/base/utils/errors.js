export const formatError = ( apiError ) => {
	return typeof apiError === 'object' && apiError.hasOwnProperty( 'message' ) ? {
		apiMessage: apiError.message,
	} : {
		// If we can't get any message from the API, set it to null and
		// let <ApiErrorPlaceholder /> handle the message to display.
		apiMessage: null,
	};
};
