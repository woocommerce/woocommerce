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
 * @param {string}               selector    The selector for the desired element
 * @param {Page | ElementHandle} [root=page] The root from which to search for the selector
 *
 * @return {Promise<boolean>} Whether the element exists or not
 */
export async function elementExists( selector, root = page ) {
	return !! ( await root.$( selector ) );
}

/**
 * Gets data from an HTML element
 *
 * @param {string}               selector The selector for the desired element
 * @param {string}               dataKey  The key in the element data to look for
 * @param {Page | ElementHandle} root     The root from which to search for the selector
 *
 * @return {Promise<string | undefined>} The data of that element if it exists
 */
export async function getElementData( selector, dataKey, root = page ) {
	return root.$eval(
		selector,
		( $element, key ) => {
			return $element.dataset[ key ];
		},
		dataKey
	);
}

/**
 * Gets the text value of an element
 *
 * If the element is an `input` it will get the `value`, otherwise,
 * it will get the `textContent`.
 *
 * @example
 * ```js
 * const text = await getTextContent( '.my-element' );
 * ```
 * @example
 * ```js
 * const [ singleText ] = await getTextContent( '.my-single-element' );
 * ```
 *
 * @param {string}               selector    The selector for the desired element
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
