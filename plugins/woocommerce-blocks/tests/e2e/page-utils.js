/**
 * @file Generic utility functions for Puppeteer
 */

/**
 * @typedef {import('@types/puppeteer').Page} Page
 * @typedef {import('@types/puppeteer').ElementHandle} ElementHandle
 * @typedef {import('@wordpress/blocks').Block} WPBlock
 */

/**
 * Checks whether an element exists under a certain context
 *
 * @param {string} selector The selector for the desired element
 * @param {Page | ElementHandle} [root=page] The root from which to search for the selector
 *
 * @return {Promise<boolean>} Whether the element exists or not
 */
export async function elementExists( selector, root = page ) {
	return !! ( await root.$( selector ) );
}

/**
 * Gets the text value of an element
 *
 * If the element is an `input` it will get the `value`, otherwise,
 * it will get the `textContent`.
 *
 * @param {string} selector The selector for the desired element
 * @param {Page | ElementHandle} [root=page] The root from which to search for the selector
 *
 * @return {Promise<string[]>} An array of text contained in those selected elements
 */
export function getTextContent( selector, root = page ) {
	return root.$$eval( selector, ( $elements ) => {
		return $elements.map(
			( $element ) => $element.value || $element.textContent
		);
	} );
}
