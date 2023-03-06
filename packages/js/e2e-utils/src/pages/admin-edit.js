import { waitForTimeout } from '../flows/utils';

export class AdminEdit {
	/**
	 * Publish the object being edited and verify published status
	 *
	 * @param {string} button              Publish button selector
	 * @param {string} publishNotice       Publish notice selector
	 * @param {string} publishVerification Expected notice on successful publish
	 * @return {Promise<void>} Promise resolving when the object is published
	 */
	async verifyPublish( button, publishNotice, publishVerification ) {
		// Wait for auto save
		await waitForTimeout( 2000 );

		// Publish and verify
		await expect( page ).toClick( button );
		await page.waitForSelector( publishNotice );
		await expect( page ).toMatchElement( publishNotice, {
			text: publishVerification,
		} );
	}

	/**
	 * Get the ID of the object being edited
	 *
	 * @return {Promise<*>} Promise resolving to the ID of the object being edited.
	 */
	async getId() {
		const postId = await page.$( '#post_ID' );
		const objectID = await page.evaluate(
			( element ) => element.value,
			postId
		);
		return objectID;
	}
}
