module.exports = {
	extends: '@wordpress/stylelint-config/scss-stylistic',
	ignoreFiles: ['./vendor/**/*.scss'],
	rules: {
		'at-rule-empty-line-before': null,
		'at-rule-no-unknown': null,
		'comment-empty-line-before': null,
		'declaration-block-no-duplicate-properties': null,
		'@stylistic/declaration-colon-newline-after': null,
		'declaration-property-unit-allowed-list': null,
		'font-weight-notation': null,
		'@stylistic/max-line-length': null,
		'no-descending-specificity': null,
		'no-duplicate-selectors': null,
		'rule-empty-line-before': null,
		'selector-class-pattern': null,
		'@stylistic/string-quotes': 'double',
		'value-keyword-case': null,
		'@stylistic/value-list-comma-newline-after': null,
		// TODO: fix these rules
		// New rules enabled after updating @wordpress/stylelint-config
		'scss/load-partial-extension': 'always',
		'scss/load-no-partial-leading-underscore': null,
		'scss/no-global-function-names': null,
		'scss/operator-no-unspaced': null,
		'scss/at-extend-no-missing-placeholder': null,
		'scss/selector-no-redundant-nesting-selector': null,
		'selector-id-pattern': null,
		'no-invalid-position-at-import-rule': null,
		'length-zero-no-unit': [true, { ignoreFunctions: ['calc', 'var'] }],
		// Enabled by the preset since @wordpress/stylelint-config 23.x.
		// TODO: re-enable once update-banner.scss uses valid WPDS tokens.
		'plugin-wpds/no-unknown-ds-tokens': null,
	},
};
