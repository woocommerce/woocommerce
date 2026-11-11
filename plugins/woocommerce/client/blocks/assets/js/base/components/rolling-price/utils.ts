export const DIGITS = '0123456789';

export interface RollingCharacter {
	// Stable React key: the place from the end of the amount.
	key: string;
	character: string;
	// The digit the column rolls to, or `null` for a separator.
	digit: number | null;
	// The digit the column starts from when it first appears.
	fromDigit: number;
}

/**
 * Splits a formatted amount, such as "1,234.50", into the characters of a price roll. Each digit gets the digit at
 * the same place in `from`, so that it rolls from the old price. Places count from the end of the amount, because
 * the decimals stay in place when a price gains or loses a digit, for example from "999.99" to "1,000.00". A digit
 * with no old digit at its place rolls from 0.
 *
 * @param from The amount that the price rolls from.
 * @param to   The amount that the price rolls to.
 * @return One entry for each character of `to`.
 */
export const getRollingCharacters = (
	from: string,
	to: string
): RollingCharacter[] => {
	const fromCharacters = Array.from( from );
	const toCharacters = Array.from( to );

	return toCharacters.map( ( character, index ) => {
		const place = toCharacters.length - 1 - index;
		const digit = DIGITS.indexOf( character );
		const fromDigit = DIGITS.indexOf(
			fromCharacters[ fromCharacters.length - 1 - place ] ?? '-'
		);
		return {
			key: String( place ),
			character,
			digit: digit === -1 ? null : digit,
			fromDigit: Math.max( fromDigit, 0 ),
		};
	} );
};
