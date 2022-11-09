/**
 * External dependencies
 */
import { canvas } from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import { selectBlockByName } from '.';
import SELECTORS from './selectors';

/**
 * Inserts an inner block into the currently selected block. If a parent block
 * is provided, it will be selected before inserting the inner block.
 *
 * We make the parentBlockName optional to add more flexibility to the function,
 * enabling tests to have more control over the selection of the parent block.
 *
 * @param  blockTitle      Block title, such as "Add to Cart Button".
 * @param  parentBlockName Parent block name, such as core/group.
 */
export const insertInnerBlock = async (
	blockTitle: string,
	parentBlockName = ''
) => {
	if ( parentBlockName ) {
		await selectBlockByName( parentBlockName );
	}
	const blockInserterButton = await canvas().waitForSelector(
		SELECTORS.quickInserter.button
	);
	await blockInserterButton.click();
	const blockInsertInput = await page.waitForSelector(
		SELECTORS.quickInserter.searchInput
	);
	await blockInsertInput.focus();
	await page.keyboard.type( blockTitle );
	const insertButton = await page.waitForXPath(
		`//button//span[contains(text(), '${ blockTitle }')]`
	);
	await insertButton.click();
};
