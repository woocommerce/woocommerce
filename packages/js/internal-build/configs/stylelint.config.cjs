/**
 * Shared stylelint config for the monorepo's SCSS: the WordPress SCSS preset
 * (with stylistic rules) plus the relaxations that the existing code relies on.
 *
 * Usage, from a package's `.stylelintrc.json`:
 *   { "extends": "@woocommerce/internal-build/configs/stylelint.config.cjs" }
 */
module.exports = {
	extends: '@wordpress/stylelint-config/scss-stylistic',
	rules: {
		'no-descending-specificity': null,
		'no-duplicate-selectors': null,
		'selector-class-pattern': null,
		'at-rule-empty-line-before': null,
		'comment-empty-line-before': null,
		'font-weight-notation': null,
		'rule-empty-line-before': null,
		'value-keyword-case': null,
		'@stylistic/declaration-colon-newline-after': null,
		'@stylistic/function-parentheses-space-inside': null,
		'@stylistic/indentation': null,
		'@stylistic/max-line-length': null,
		// TODO: fix these rules. They were added to the preset after most of
		// the existing SCSS was written.
		'scss/load-partial-extension': null,
		'scss/load-no-partial-leading-underscore': null,
		'scss/no-global-function-names': null,
		'scss/at-extend-no-missing-placeholder': null,
		'scss/selector-no-redundant-nesting-selector': null,
		'selector-id-pattern': null,
		'no-invalid-position-at-import-rule': null,
		'length-zero-no-unit': [ true, { ignoreFunctions: [ 'calc', 'var' ] } ],
	},
	overrides: [
		{
			// CSS modules use `:global`/`:local` and the `composes` property.
			files: [ '**/*.module.css' ],
			rules: {
				'selector-pseudo-class-no-unknown': [
					true,
					{ ignorePseudoClasses: [ 'global', 'local' ] },
				],
				'property-no-unknown': [
					true,
					{ ignoreProperties: [ 'composes' ] },
				],
			},
		},
	],
};
