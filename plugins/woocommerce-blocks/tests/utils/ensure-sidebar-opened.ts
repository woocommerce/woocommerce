/**
 * Verifies that the edit post sidebar is opened, and if it is not, opens it.
 *
 * We're using the older version of wordpress/scripts, so this is needed to
 * fix the issue of the sidebar not opening.
 *
 * @todo Remove custom ensureSidebarOpened() once we upgrade to the latest version of wordpress/scripts.
 *
 * @return {Promise} Promise resolving once the edit post sidebar is opened.
 */
export async function ensureSidebarOpened(): Promise< void > {
	const toggleSidebarButton = await page.$(
		'.edit-post-header__settings [aria-label="Settings"][aria-expanded="false"]'
	);

	if ( toggleSidebarButton ) {
		await toggleSidebarButton.click();
	}
}
