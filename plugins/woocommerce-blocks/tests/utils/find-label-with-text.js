/** @typedef {import('react')} React */

/**
 * Finds the label of a toggle control.
 *
 * @param {string} label The label associated with a toggle control.
 *
 * @return {?React.ReactElementHandle} Object that represents an in-page DOM element.
 */
export async function findLabelWithText( label ) {
	const [ toggle ] = await page.$x(
		`//div[contains(@class,"components-base-control")]//label[contains(text(), '${ label }')]`
	);
	return toggle;
}
