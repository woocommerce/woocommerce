/**
 * Gets the permalink of the current post (being viewed in the editor) if it is a normal page with the classic layout.
 * If you need the permalink of a page using the block editor, use the getBlockPagePermalink function.
 *
 * @return {Promise<string>} The permalink of the page.
 */
export async function getNormalPagePermalink() {
	await page.waitForSelector( '#sample-permalink a' );
	return await page.$eval( '#sample-permalink a', ( el ) =>
		el.getAttribute( 'href' )
	);
}

export default getNormalPagePermalink;
