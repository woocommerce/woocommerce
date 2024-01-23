type TypeStringOptions = {
	delayBase: number;
};

/**
 * Simulate typing a string
 *
 * @param {string}   text     - The string to type
 * @param {Function} callback - The callback to call after each character is typed
 *
 * @return {void}
 */
export default function typeString(
	text: string,
	callback: ( text: string ) => void,
	options: TypeStringOptions = { delayBase: 80 }
): void {
	let i = 0;

	function type() {
		if ( i < text.length ) {
			callback( text.substring( 0, i + 1 ) );
			i++;

			const delay = Math.pow( Math.random(), 2 ) * options.delayBase;
			setTimeout( type, delay );
		}
	}

	type();
}
