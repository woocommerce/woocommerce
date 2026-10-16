const config = require( '@wordpress/prettier-config' );

module.exports = {
	...config,
	overrides: [
		...config.overrides,
		{
			files: [ '**/templates/**/*.html', '**/parts/**/*.html' ],
			options: {
				// Preserve inline styles used to validate serialized blocks.
				embeddedLanguageFormatting: 'off',
			},
		},
	],
};
