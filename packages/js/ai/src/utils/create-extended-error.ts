export type UseCompletionError = Error & { code?: string; cause?: Error };

export const createExtendedError = (
	msg: string,
	code?: string,
	cause?: Error
) =>
	Object.assign( new Error( msg ), {
		code,
		cause,
	} );
