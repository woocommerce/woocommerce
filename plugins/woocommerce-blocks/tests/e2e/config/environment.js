/**
 * External dependencies
 */
const PuppeteerEnvironment = require( 'jest-environment-puppeteer' );
const { addAttach } = require( 'jest-html-reporters/helper' );

class E2EEnvironment extends PuppeteerEnvironment {
	async handleTestEvent( event ) {
		if ( event.name === 'test_fn_failure' ) {
			const data = await this.global.page.screenshot();
			await addAttach( data, 'Full Page Screenshot', this.global );
		}
	}
}

module.exports = E2EEnvironment;
