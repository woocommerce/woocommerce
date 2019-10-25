/**
 * Internal dependencies
 */
const flows = require( './flows' );

/**
 * Click a tab (on post type edit screen).
 *
 * @param {string} tabName Tab label
 */
const clickTab = async ( tabName ) => {
	await expect( page ).toClick( '.wc-tabs > li > a', { text: tabName } );
};

/**
 * Wait for UI blocking to end.
 */
const uiUnblocked = async () => {
	await page.waitForFunction( () => ! Boolean( document.querySelector( '.blockUI' ) ) );
};

/**
 * Publish, verify that item was published. Trash, verify that item was trashed.
 *
 * @param {string} button (Publish)
 * @param {string} publishNotice
 * @param {string} publishVerification
 * @param {string} trashVerification
 */
const verifyPublishAndTrash = async ( button, publishNotice, publishVerification, trashVerification ) => {
	// Wait for auto save
	await page.waitFor( 2000 );

	// Publish
	await expect( page ).toClick( button );
	await page.waitForSelector( publishNotice );

	// Verify
	await expect( page ).toMatchElement( publishNotice, { text: publishVerification } );
	if ( button === '.order_actions li .save_order' ) {
		await expect( page ).toMatchElement( '#select2-order_status-container', { text: 'Processing' } );
		await expect( page ).toMatchElement(
			'#woocommerce-order-notes .note_content',
			{
				text: 'Order status changed from Pending payment to Processing.',
			}
		);
	}

	// Trash
	await expect( page ).toClick( 'a', { text: "Move to Trash" } );
	await page.waitForSelector( '#message' );

	// Verify
	await expect( page ).toMatchElement( publishNotice, { text: trashVerification } );
};

module.exports = {
	...flows,
	clickTab,
	uiUnblocked,
	verifyPublishAndTrash,
};
