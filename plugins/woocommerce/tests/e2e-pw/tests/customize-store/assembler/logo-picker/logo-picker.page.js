const { encodeCredentials } = require( '../../../../utils/plugin-utils' );

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
		await assemblerLocator.getByText( 'Media Library' ).click();

		await assemblerLocator.getByLabel( 'image-03' ).click();
		await assemblerLocator
			.getByRole( 'button', { name: 'Select', exact: true } )
			.click();
	}

	async resetLogo( baseURL ) {
		const apiContext = await this.request.newContext( {
			baseURL,
			extraHTTPHeaders: {
				Authorization: `Basic ${ encodeCredentials(
					'admin',
					'password'
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
		await assemblerLocator.getByText( 'Save' ).click();
		const waitForLogoResponse = this.page.waitForResponse(
			( response ) =>
				response.url().includes( 'wp-json/wp/v2/settings' ) &&
				response.status() === 200
		);
		const waitForHeaderResponse = this.page.waitForResponse(
			( response ) =>
				response
					.url()
					.includes(
						'wp-json/wp/v2/template-parts/twentytwentyfour//header'
					) && response.status() === 200
		);
		await Promise.all( [ waitForLogoResponse, waitForHeaderResponse ] );
	}
}
