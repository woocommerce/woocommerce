/**
 * Compare strings in code unit order, with missing values last.
 *
 * @param a First value to compare.
 * @param b Second value to compare.
 */
export const compareStrings = (
	a: string | null | undefined,
	b: string | null | undefined
): number => {
	if ( a == null ) {
		return b == null ? 0 : 1;
	}
	if ( b == null ) {
		return -1;
	}
	return a < b ? -1 : Number( a > b );
};
