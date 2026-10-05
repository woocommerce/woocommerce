module.exports = {
	extends: '@woocommerce/internal-build/configs/stylelint.config.cjs',
	reportNeedlessDisables: true,
	ignoreFiles: [ './vendor/**/*.scss' ],
	rules: {
		'declaration-property-unit-allowed-list': null,
		'scss/load-partial-extension': 'always',
		// Enabled by the preset since @wordpress/stylelint-config 23.x.
		// TODO: re-enable once update-banner.scss uses valid WPDS tokens.
		'plugin-wpds/no-unknown-ds-tokens': null,
	},
};
