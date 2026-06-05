/**
 * Lazy plugin exports.
 *
 * The `flat/recommended` config is loaded on demand (via a getter) so that
 * loading this module from inside its own FlatCompat chain does not create a
 * circular require loop. The rule is loaded eagerly because it has no
 * dependencies and is cheap to keep ready.
 */

const plugin = {
	configs: {},
	rules: {
		'dependency-group': require( './rules/dependency-group' ),
	},
};

Object.defineProperties( plugin.configs, {
	recommended: {
		enumerable: true,
		get() {
			return require( './configs/recommended' );
		},
	},
	'flat/recommended': {
		enumerable: true,
		get() {
			return require( './configs/flat/recommended' );
		},
	},
} );

module.exports = plugin;
