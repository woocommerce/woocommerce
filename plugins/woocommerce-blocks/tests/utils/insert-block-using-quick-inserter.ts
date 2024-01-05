/**
 * External dependencies
 */
import { canvas } from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import SELECTORS from './selectors';

export const insertBlockUsingQuickInserter = async ( blockTitle: string ) => {
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
