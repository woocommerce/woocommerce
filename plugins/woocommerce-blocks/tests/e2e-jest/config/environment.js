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

// This code is helpful for tracing every test that is executed.
// You should use this code if your test fails, but Jest doesn't give you a significant error, and you need to debug.
// async handleTestEvent( event ) {
// 	const ignoredEvents = [
// 		'setup',
// 		'add_hook',
// 		'start_describe_definition',
// 		'add_test',
// 		'finish_describe_definition',
// 		'run_start',
// 		'run_describe_start',
// 		'test_start',
// 		'hook_start',
// 		'hook_success',
// 		'test_fn_start',
// 		'test_fn_success',
// 		'test_done',
// 		'run_describe_finish',
// 		'run_finish',
// 		'teardown',
// 	];
// 	if ( ! ignoredEvents.includes( event.name ) ) {
// 		// eslint-disable-next-line no-console
// 		console.log(
// 			new Date().toString() +
// 				' Unhandled event(' +
// 				event.name +
// 				'): ' +
// 				util.inspect( event )
// 		);
// 	}
// }

module.exports = E2EEnvironment;
