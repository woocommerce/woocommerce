/**
 * Function that scrolls to an element after disabling smooth scrolling in the page.
 */
export const scrollTo = async ( selectorArg: string ): Promise< void > => {
	await page.evaluate( ( selector ) => {
		// Disable smooth scrolling so it scrolls instantly.
		document.querySelector( 'html' ).style.scrollBehavior = 'auto';
		document.querySelector( selector ).scrollIntoView( {
			block: 'center',
			inline: 'center',
		} );
	}, selectorArg );
};
