export async function preventCompatibilityNotice() {
	await page.evaluate( () => {
		window.localStorage.setItem(
			'wc-blocks_dismissed_compatibility_notices',
			'["checkout"]'
		);
	} );
}

export async function reactivateCompatibilityNotice() {
	await page.evaluate( () => {
		window.localStorage.removeItem(
			'wc-blocks_dismissed_compatibility_notices'
		);
	} );
}
