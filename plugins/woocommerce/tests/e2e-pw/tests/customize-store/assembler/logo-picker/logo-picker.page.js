const { encodeCredentials } = require( '../../../../utils/plugin-utils' );
const { admin } = require( '../../../../test-data/data' );

export class LogoPickerPage {
	page;
	request;
	constructor( { page, request } ) {
		this.page = page;
		this.request = request;
	}

	/**
	 *
	 * @param {(import('@playwright/test').Page | import('@playwright/test').FrameLocator)} assemblerLocator
	 * @return {import('@playwright/test').Locator} Locator for the logo picker when no logo is set
	 */
	getEmptyLogoPickerLocator( assemblerLocator ) {
		return assemblerLocator.locator(
			'.block-library-site-logo__inspector-upload-container'
		);
	}

	/**
	 *
	 * @param {(import('@playwright/test').Page | import('@playwright/test').FrameLocator)} assemblerLocator
	 * @return {import('@playwright/test').Locator} Locator for the logo picker when a logo is set
	 */
	getLogoPickerLocator( assemblerLocator ) {
		return assemblerLocator.locator(
			'.woocommerce-customize-store__sidebar-logo-container'
		);
	}

	/**
	 * @param {(import('@playwright/test').Page | import('@playwright/test').FrameLocator)} editorOrPageLocator
	 * @return {import('@playwright/test').Locator} Locator for the logo in the editor or page
	 */
	getLogoLocator( editorOrPageLocator ) {
		return editorOrPageLocator.locator( 'header img.custom-logo' );
	}

	async pickImage( assemblerLocator ) {
		await assemblerLocator
			.getByRole( 'tab', { name: 'Media Library' } )
			.click();

		await assemblerLocator.getByLabel( 'image-03' ).first().click();
		await assemblerLocator
			.getByRole( 'button', { name: 'Select', exact: true } )
			.click();
	}

	getPlaceholderPreview( assemblerLocator ) {
		return assemblerLocator.locator( '.components-placeholder__preview' );
	}

	async resetLogo( baseURL ) {
		const apiContext = await this.request.newContext( {
			baseURL,
			extraHTTPHeaders: {
				Authorization: `Basic ${ encodeCredentials(
					admin.username,
					admin.password
				) }`,
				cookie: '',
			},
		} );

		await apiContext.post( '/wp-json/wp/v2/settings', {
			data: {
				site_logo: null,
			},
		} );
	}

	async saveLogoSettings( assemblerLocator ) {
		const waitForLogoResponse = this.page.waitForResponse(
			( response ) =>
				response.url().includes( 'wp-json/wp/v2/settings' ) &&
				response.status() === 200
		);
		await assemblerLocator.locator( '[aria-label="Back"]' ).click();
		await assemblerLocator
			.getByRole( 'button', { name: 'Finish customizing', exact: true } )
			.waitFor();
		await Promise.all( [
			waitForLogoResponse,
			assemblerLocator.getByText( 'Finish customizing' ).click(),
		] );
		await assemblerLocator.getByText( 'Your store looks great!' ).waitFor();
	}
}
