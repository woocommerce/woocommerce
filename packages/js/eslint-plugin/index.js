/**
 * Deduplicate plugin references across config objects so that consumers can
 * spread configs (e.g. `...woocommerce.configs.recommended`) without hitting
 * ESLint's "Cannot redefine plugin" error.
 *
 * When the same plugin namespace appears in multiple config objects, this
 * ensures all references point to the same object instance. The configs we
 * spread from `@wordpress/eslint-plugin` are already deduped upstream; this
 * reconciles them against the layer we add on top.
 *
 * Unlike the upstream helper this one is pure. Our config array spreads objects
 * owned by `@wordpress/eslint-plugin` and `typescript-eslint`, and rewriting
 * their `plugins` in place would mutate those shared module singletons for every
 * other consumer in the process.
 *
 * @param {Array} configs Array of flat config objects.
 * @return {Array} New config objects whose duplicate plugin keys share a single
 *                 reference.
 */
function dedupePlugins( configs ) {
	const seen = Object.create( null );
	return configs.map( ( config ) => {
		if ( ! config.plugins ) {
			return config;
		}
		const plugins = { ...config.plugins };
		for ( const name of Object.keys( plugins ) ) {
			if ( name in seen ) {
				plugins[ name ] = seen[ name ];
			} else {
				seen[ name ] = plugins[ name ];
			}
		}
		return { ...config, plugins };
	} );
}

module.exports = {
	meta: {
		name: '@woocommerce/eslint-plugin',
		version: require( './package.json' ).version,
	},
	configs: {
		recommended: dedupePlugins( require( './configs/recommended' ) ),
	},
};
