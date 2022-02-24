/**
 * External dependencies
 */
const PuppeteerEnvironment = require( 'jest-environment-puppeteer' );
const { addAttach } = require( 'jest-html-reporters/helper' );

class E2EEnvironment extends PuppeteerEnvironment {
	async handleTestEvent( event ) {
		if (
			event.name === 'test_fn_failure' ||
			event.name === 'hook_failure'
		) {
			const attach = await this.global.page.screenshot( {
				fullPage: event.name !== 'hook_failure',
			} );
			await addAttach( {
				attach,
				description: 'Full Page Screenshot',
				context: this.global,
				bufferFormat: 'png',
			} );
		}
	}
}

module.exports = E2EEnvironment;
