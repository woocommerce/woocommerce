/**
 * Generates an array of sequentially numbered strings in the format "string number".
 *
 * @param name   The base string to be used.
 * @param number The number of times the string should be repeated with an incremented number.
 * @return An array of formatted strings.
 */
export function getEmptyStateSequentialNames(
	name: string,
	number: number
): string[] {
	return Array( number )
		.fill( 0 )
		.map( ( _, index ) => `${ name } ${ index + 1 }` );
}
