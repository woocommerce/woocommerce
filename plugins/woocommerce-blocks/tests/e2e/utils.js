/**
 * External dependencies
 */
import { searchForBlock } from '@wordpress/e2e-test-utils';

/**
 * Opens the inserter, searches for the given term, then selects the first
 * result that appears.
 *
 * @param {string} searchTerm The text to search the inserter for.
 */
export async function insertBlockDontWaitForInsertClose( searchTerm ) {
	await searchForBlock( searchTerm );
	const insertButton = (
		await page.$x( `//button//span[contains(text(), '${ searchTerm }')]` )
	 )[ 0 ];
	await insertButton.click();
}

export const closeInserter = async () => {
	await page.click( '.edit-post-header [aria-label="Add block"]' );
};
