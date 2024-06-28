/**
 * Waits for the spinner to appear and disappear,
 * indicating that attributes have been loaded.
 *
 * This function confirms that the attributes have been
 * loaded by waiting for a spinner to appear and then disappear.
 * The spinner indicates that a loading process is occurring,
 * and its disappearance indicates that the process is complete.
 *
 * @param {Object} page - The Playwright Page object.
 */
export async function waitForGlobalAttributesLoaded( page ) {
	const spinnerLocator = page.locator(
		'.woocommerce-new-attribute-modal__table-row .components-spinner'
	);
	await spinnerLocator.waitFor( { state: 'visible' } );
	await spinnerLocator.waitFor( { state: 'hidden' } );
}
