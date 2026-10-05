/**
 * Base stylelint config shared across the monorepo: the WordPress SCSS preset
 * (with stylistic rules) plus the rules that every package turns off.
 *
 * Packages that want the usual relaxations should extend `stylelint.config.cjs`
 * instead. Use this one directly only when a package needs to keep more of the
 * preset enabled (see `plugins/woocommerce/client/legacy`).
 *
 * Usage, from a package's `.stylelintrc.json`:
 *   { "extends": "@woocommerce/internal-build/configs/stylelint-base.cjs" }
 */
module.exports = {
	extends: '@wordpress/stylelint-config/scss-stylistic',
	rules: {
		'no-descending-specificity': null,
		'no-duplicate-selectors': null,
		'selector-class-pattern': null,
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
