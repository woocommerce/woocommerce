export const decodeHtmlEntities = ( () => {
	const textArea = document.createElement( 'textarea' );
	return ( str: string ): string => {
		textArea.innerHTML = str;
		return textArea.value;
	};
} )();
