/**
 * Generates a random unique ID as a number.
 *
 * @return {number} The generated unique ID as a number.
 */
export function generateUniqueId(): number {
	return Math.floor( Math.random() * Date.now() );
}
