/**
 * Click a link and wait for the page to load.
 *
 * @param {string} selector The CSS selector of the link to click.
 */
export const clickLink = async ( selector ) => {
	await Promise.all( [
		page.click( selector ),
		page.waitForNavigation( { waitUntil: 'networkidle0' } ),
	] );
};
