export interface ErrorObject {
	/**
	 * Human-readable error message to display.
	 */
	message: string;
	/**
	 * Context in which the error was triggered. That will determine how the error is displayed to the user.
	 */
	type: 'api' | 'general' | string;
}

type SimpleError = {
	message: string;
	type?: string;
};

export const formatError = async (
	error: SimpleError | Response
): Promise< ErrorObject > => {
	if ( 'json' in error ) {
		try {
			const parsedError = await error.json();
			return {
				message: parsedError.message,
				type: parsedError.type || 'api',
			};
		} catch ( e ) {
			return {
				// We could only return this if e is instanceof Error but, to avoid changing runtime
				// behaviour, we'll just cast it instead.
				message: ( e as Error ).message,
				type: 'general',
			};
		}
	} else {
		return {
			message: error.message,
			type: error.type || 'general',
		};
	}
};
