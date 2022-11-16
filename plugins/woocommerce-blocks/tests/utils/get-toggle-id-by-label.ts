/**
 * Internal dependencies
 */
import { DEFAULT_TIMEOUT } from './constants';

/**
 * Get the ID of the setting toogle so test can manipulate the toggle using
 * unsetCheckbox and setCheckbox utilities.
 *
 * We're using 'adaptive timeout' here as the id of the toggle is changed on
 * every render, so we wait a bit for the toggle to finish rerendering, then
 * check if the node still attached to the document before returning its
 * ID. If the node is detached, it means that the toggle is rendered, then
 * we retry by calling this function again with increased retry argument. We
 * will retry until the timeout is reached.
 */
export const getToggleIdByLabel = async (
	label: string,
	retry = 0
): Promise< string > => {
	const delay = 1000;
	const labelElement = await page.waitForXPath(
		`//label[contains(text(), "${ label }") and contains(@class, "components-toggle-control__label")]`,
		{ visible: true }
	);
	const checkboxId = await page.evaluate(
		( toggleLabel ) => `#${ toggleLabel.getAttribute( 'for' ) }`,
		labelElement
	);
	// Wait a bit for toggle to finish rerendering.
	await page.waitForTimeout( delay );
	const checkbox = await page.$( checkboxId );
	if ( ! checkbox ) {
		if ( retry * delay < DEFAULT_TIMEOUT ) {
			return await getToggleIdByLabel( label, retry + 1 );
		}
		throw new Error( `Can't find toggle with label ${ label }` );
	}
	return checkboxId;
};
