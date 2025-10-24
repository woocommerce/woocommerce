let config = require( '../../playwright.config.js' );
const { tags } = require( '../../fixtures/fixtures' );

process.env.USE_WP_ENV = 'true';

config = {
	...config.default,
	projects: [
		...config.setupProjects,
		{
			name: 'Gutenberg',
			grep: new RegExp( tags.GUTENBERG ),
			// Customise Your Store tests are failing with Gutenberg nightly, since 21.7RC
			// and change causing the fail will most likely land in 21.8 stable.
			// We're disabling them as we're considering sunsetting CYS in 10.3.
			testIgnore: [ '**/customize-store/**' ],
			dependencies: [ 'site setup' ],
		},
	],
};

module.exports = config;
