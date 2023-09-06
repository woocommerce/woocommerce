export const encodeHtmlEntities = ( str: string ) =>
	str.replace(
		/[&<>'"]/g,
		( tag ) =>
			( {
				'&': '&amp;',
				'<': '&lt;',
				'>': '&gt;',
				"'": '&#39;',
				'"': '&quot;',
			}[ tag ] || tag )
	);

export const decodeHtmlEntities = ( () => {
	const textArea = document.createElement( 'textarea' );
	return ( str: string ): string => {
		textArea.innerHTML = str;
		return textArea.value;
	};
} )();
