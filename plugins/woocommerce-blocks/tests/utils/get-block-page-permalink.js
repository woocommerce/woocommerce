/**
 * External dependencies
 */
import {
	ensureSidebarOpened,
	findSidebarPanelToggleButtonWithTitle,
} from '@wordpress/e2e-test-utils';

/**
 * Internal dependencies
 */
import { visitBlockPage } from './visit-block-page';

/**
 * Gets the permalink of a page where the block editor is in use.
 *
 * @param {string} blockPage The name of the page whose permalink you want to get.
 * @return {Promise<string>} Returns the permalink of the page.
 */
export async function getBlockPagePermalink( blockPage ) {
	await visitBlockPage( blockPage );
	await ensureSidebarOpened();
	const panelButton = await findSidebarPanelToggleButtonWithTitle(
		'Permalink'
	);
	const ensureLinkClickable = async ( page ) => {
		let linkVisible =
			( await page.$( '.edit-post-post-link__link' ) ) !== null;
		while ( ! linkVisible ) {
			await panelButton.click( 'button' );
			page.waitForTimeout( 300 );
			linkVisible =
				( await page.$( '.edit-post-post-link__link' ) ) !== null;
		}
	};

	await ensureLinkClickable( page );
	const link = await page.$eval( '.edit-post-post-link__link', ( el ) => {
		return el.getAttribute( 'href' );
	} );
	return link;
}

export default getBlockPagePermalink;
