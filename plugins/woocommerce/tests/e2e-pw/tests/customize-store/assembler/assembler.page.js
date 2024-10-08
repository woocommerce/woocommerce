export class AssemblerPage {
	page;
	constructor( { page } ) {
		this.page = page;
	}

	async setupSite( baseUrl ) {
		const DESIGN_URL =
			baseUrl +
			'/wp-admin/admin.php?page=wc-admin&path=%2Fcustomize-store%2Fdesign';
		await this.page.goto( DESIGN_URL );
	}

	async waitForLoadingScreenFinish() {
		const frame = this.page.frameLocator(
			'.cys-fullscreen-iframe[style="opacity: 1;"]'
		);

		await frame
			.getByRole( 'button', { name: 'Finish customizing' } )
			.waitFor( { timeout: 25000 } );
	}

	/**
	 * When the user passes for the loading screen, the assembler is loaded in an iframe.
	 * Otherwise it is loaded in the page itself.
	 *
	 * @return {(Promise<import('playwright').Frame> | Promise<import('playwright').Page>)} The assembler page or the iframe where the assembler is loaded.
	 */
	async getAssembler() {
		const selector = '.cys-fullscreen-iframe[style="opacity: 1;"]';

		if ( await this.page.isVisible( selector ) ) {
			return this.page.frameLocator( selector );
		}
		return this.page;
	}

	/**
	 * Get the editor frame locator.
	 *
	 * @return {Promise<import('playwright').FrameLocator>} The editor frame locator.
	 */
	async getEditor() {
		const assembler = await this.getAssembler();
		return assembler.frameLocator(
			'.woocommerce-customize-store__edit-site-editor [name="editor-canvas"]'
		);
	}
}
