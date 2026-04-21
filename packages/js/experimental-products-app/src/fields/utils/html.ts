export function convertHtmlToPlainText( html: string ) {
	if ( ! html ) {
		return '';
	}

	if ( typeof document === 'undefined' ) {
		return html
			.replace( /<[^>]*>/g, ' ' )
			.replace( /\s+/g, ' ' )
			.trim();
	}

	const container = document.createElement( 'div' );
	container.innerHTML = html;

	return ( container.textContent || '' ).replace( /\s+/g, ' ' ).trim();
}
