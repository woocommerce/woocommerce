/**
 * External dependencies
 */
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const path = require( 'path' );

/**
 * Internal dependencies
 */
const { getAlias } = require( '../bin/webpack-helpers.js' );
const {
	getMainConfig,
	getStylingConfig,
} = require( '../bin/webpack-configs.js' );
const tsConfig = require( '../tsconfig.base.json' );

const aliases = Object.keys( tsConfig.compilerOptions.paths ).reduce(
	( acc, key ) => {
		// Filter out @wordpress/* paths to allow resolution from node_modules instead of build-types directory specified in tsconfig.
		if ( ! key.startsWith( '@wordpress' ) ) {
			const currentPath = tsConfig.compilerOptions.paths[ key ][ 0 ];
			// Skip type-only mappings (`.d.ts`). These exist so `tsc` can
			// resolve a package's types; turning them into webpack aliases
			// makes the bundler import the declaration file instead of the
			// runtime module (e.g. `dinero.js/currencies`). Let those resolve
			// normally via the package's exports.
			if ( ! currentPath.endsWith( '.d.ts' ) ) {
				acc[ key.replace( '/*', '' ) ] = path.resolve(
					__dirname,
					'../' + currentPath.replace( '/*', '/' )
				);
			}
		}
		return acc;
	},
	{}
);

module.exports = ( { config: storybookConfig } ) => {
	const wooBlocksConfig = getMainConfig( { alias: getAlias() } );
	const wooStylingConfig = getStylingConfig();
	storybookConfig.resolve.alias = {
		...storybookConfig.resolve.alias,
		...aliases,
		'@woocommerce/block-settings': require.resolve(
			'./__mocks__/woocommerce-block-settings.js'
		),
		'@woocommerce/base-hooks': require.resolve(
			'./__mocks__/woocommerce-base-hooks.js'
		),
		'wordpress-components': require.resolve(
			'../node_modules/wordpress-components'
		),
	};

	// Resolve `@woocommerce/*` monorepo packages from source via the `wc-source`
	// export condition, mirroring the blocks' main webpack resolve. Required now
	// that the package JS build outputs are no longer produced by a build cascade.
	storybookConfig.resolve.conditionNames = [ 'wc-source', '...' ];

	storybookConfig.module.rules.push(
		{
			test: /\/stories\/.+\.js$/,
			use: [
				{
					loader: require.resolve( '@storybook/source-loader' ),
					options: { parser: 'typescript' },
				},
			],
			enforce: 'pre',
		},
		...wooBlocksConfig.module.rules,
		...wooStylingConfig.module.rules
	);

	storybookConfig.plugins.push(
		new MiniCssExtractPlugin( {
			filename: `[name].css`,
		} )
	);

	storybookConfig.module.rules = storybookConfig.module.rules.filter(
		( rule ) =>
			! (
				rule.use &&
				typeof rule.use.loader === 'string' &&
				rule.use.loader.indexOf( 'babel-loader' ) >= 0
			)
	);

	// Storybook's default babel rule excludes node_modules, but `@woocommerce/*`
	// packages now resolve to their TypeScript source (symlinked under
	// node_modules), so that source must be transpiled. Added after the filter
	// above so it isn't stripped. Uses the project's babel.config.js. Scoped to
	// Storybook — does not touch the shared plugin webpack config.
	storybookConfig.module.rules.push( {
		test: /\.(j|t)sx?$/,
		// pnpm resolves workspace packages to their real path, so `@woocommerce/*`
		// source appears as `packages/js/<pkg>/src` (matched here) rather than the
		// `node_modules/@woocommerce/<pkg>` symlink.
		include:
			/[\/\\](?:packages[\/\\]js|node_modules[\/\\]@woocommerce)[\/\\][^\/\\]+[\/\\]src[\/\\]/,
		loader: 'babel-loader',
	} );

	return storybookConfig;
};
