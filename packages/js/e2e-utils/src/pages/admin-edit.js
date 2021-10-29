import { waitForTimeout } from '../flows/utils';


export class AdminEdit {
	/**
	 * Publish the object being edited and verify published status
	 *
	 * @param button Publish button selector
	 * @param publishNotice Publish notice selector
	 * @param publishVerification Expected notice on successful publish
	 * @returns {Promise<void>}
	 */
	async verifyPublish( button, publishNotice, publishVerification ) {
		// Wait for auto save
		await waitForTimeout( 2000 );

		// Publish and verify
		await expect( page ).toClick( button );
		await page.waitForSelector( publishNotice );
		await expect( page ).toMatchElement( publishNotice, { text: publishVerification } );
	}

	/**
	 * Get the ID of the object being edited
	 *
	 * @returns {Promise<*>}
	 */
	async getId() {
		let postId = await page.$( '#post_ID' );
		let objectID = await page.evaluate( element => element.value, postId );
		return objectID;
	}
}
