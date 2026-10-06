module.exports = {
	extends: '@woocommerce/internal-build/configs/stylelint.config.cjs',
	ignoreFiles: [ './vendor/**/*.scss' ],
	rules: {
		'scss/load-partial-extension': 'always',
		// Enabled by the preset since @wordpress/stylelint-config 23.x.
		// TODO: re-enable once update-banner.scss uses valid WPDS tokens.
		'plugin-wpds/no-unknown-ds-tokens': null,
	},
};
