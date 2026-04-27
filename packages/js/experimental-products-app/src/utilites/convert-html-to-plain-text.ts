/**
 * Converts HTML string to plain text by stripping HTML tags
 *
 * @param htmlString - The HTML string to convert
 * @return Plain text content without HTML tags
 */
export function convertHtmlToPlainText( htmlString: string ): string {
	const tempDiv = document.createElement( 'div' );
	tempDiv.innerHTML = htmlString;
	return tempDiv.textContent || tempDiv.innerText || '';
}
