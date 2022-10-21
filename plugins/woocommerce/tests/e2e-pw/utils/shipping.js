const { Page } = require( '@playwright/test' );

/**
 * Delete shipping classes through the UI since we don't have API endpoints for shipping classes yet.
 *
 * @param { Page } adminPage
 */
const deleteAllShippingClasses = async ( adminPage ) => {
	// Navigate to Shipping classes page.
	await adminPage.goto(
		'wp-admin/admin.php?page=wc-settings&tab=shipping&section=classes'
	);

	// Count shipping classes.
	let { length: count } = await adminPage.$$( '.wc-shipping-class-delete' );

	if ( count === 0 ) {
		return;
	}

	// Delete shipping classes.
	while ( count-- ) {
		await adminPage.dispatchEvent(
			'.wc-shipping-class-delete >> nth=0',
			'click'
		);
	}

	await adminPage.click( '.wc-shipping-class-save' );
	await adminPage.waitForLoadState( 'networkidle' );
};

module.exports = {
	deleteAllShippingClasses,
};
