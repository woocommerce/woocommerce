export default function typeString(
	text: string,
	callback: ( text: string ) => void
) {
	let i = 0;

	function type() {
		if ( i < text.length ) {
			callback( text.substring( 0, i + 1 ) );
			i++;

			setTimeout( type, Math.random() * 3 + 3 );
		}
	}

	type();
}
