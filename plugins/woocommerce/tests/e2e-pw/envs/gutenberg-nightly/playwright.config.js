let config = require( '../gutenberg-stable/playwright.config.js' );
const { tags } = require( '../../fixtures/fixtures' );

config = {
	...config,
	projects: [
		...config.projects,
		{
			name: 'Gutenberg',
			grep: new RegExp( tags.GUTENBERG ),
			// Customise Your Store tests are failing with Gutenberg nightly, since 21.7RC.
			// We're disabling them as we're considering sunsetting CYS in 10.3.
			testIgnore: [ '**/customize-store/**' ],
			dependencies: [ 'site setup' ],
		},
	],
};

module.exports = config;
