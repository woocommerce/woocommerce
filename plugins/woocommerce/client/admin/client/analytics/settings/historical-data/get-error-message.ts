/**
 * Extract a user-facing message from a caught request error.
 *
 * `@wordpress/api-fetch` rejects with the parsed REST error object
 * (`{ code, message }`), which is a plain object — not an `Error` instance —
 * so narrow on the `message` property instead of the constructor.
 */
export default function getErrorMessage(
	err: unknown,
	fallback: string
): string {
	if (
		typeof err === 'object' &&
		err !== null &&
		'message' in err &&
		typeof err.message === 'string' &&
		err.message !== ''
	) {
		return err.message;
	}
	return fallback;
}
