/**
 * Finds the label of a toggle control.
 *
 * @param {string} label The label associated with a toggle control.
 *
 * @return {?ElementHandle} Object that represents an in-page DOM element.
 */
export async function findToggleWithLabel( label ) {
	const [ toggle ] = await page.$x(
		`//div[contains(@class,"components-toggle-control")]//label[contains(text(), '${ label }')]`
	);
	return toggle;
}
