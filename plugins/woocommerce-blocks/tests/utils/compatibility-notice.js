/* global localStorage */
/* eslint-disable no-unused-expressions */

export async function preventCompatibilityNotice() {
	await page.evaluate( () => {
		localStorage.setItem(
			'wc-blocks_dismissed_compatibility_notices',
			'["checkout"]'
		);
	} );
}

export async function reactivateCompatibilityNotice() {
	await page.evaluate( () => {
		localStorage.removeItem( 'wc-blocks_dismissed_compatibility_notices' );
	} );
}
4;
