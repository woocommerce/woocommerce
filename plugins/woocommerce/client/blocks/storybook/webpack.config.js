/**
 * External dependencies
 */
const MiniCssExtractPlugin = require( 'mini-css-extract-plugin' );
const path = require( 'path' );

/**
 * Internal dependencies
 */
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
	storybookConfig.resolve.alias = {
		...storybookConfig.resolve.alias,
		...aliases,
		'@woocommerce/block-settings':
			require.resolve( './__mocks__/woocommerce-block-settings.js' ),
		'@woocommerce/base-hooks':
			require.resolve( './__mocks__/woocommerce-base-hooks.js' ),
		'wordpress-components':
			require.resolve( '../node_modules/wordpress-components' ),
	};

	// Resolve `@woocommerce/*` monorepo packages from source via the `wc-source`
	// export condition, mirroring the blocks' main webpack resolve. Required now
	// that the package JS build outputs are no longer produced by a build cascade.
	storybookConfig.resolve.conditionNames = [ 'wc-source', '...' ];

	// The style rules of the old blocks webpack build, which Storybook still
	// uses: the blocks build itself now runs on Rolldown.
	storybookConfig.module.rules.push(
		{
			test: /\.s[c|a]ss$/,
			use: { loader: 'ignore-loader' },
		},
		{
			test: /\.s?css$/,
			use: [
				MiniCssExtractPlugin.loader,
				'css-loader',
				'postcss-loader',
				{
					loader: 'sass-loader',
					options: {
						sassOptions: {
							includePaths: [ 'assets/css/abstracts' ],
						},
						additionalData: ( content, loaderContext ) => {
							const relativePath = path.relative(
								loaderContext.rootContext,
								loaderContext.resourcePath
							);
							if (
								relativePath.startsWith(
									'assets/css/abstracts/'
								) ||
								relativePath.startsWith(
									'assets\\css\\abstracts\\'
								)
							) {
								return content;
							}
							return (
								'@use "sass:math";' +
								'@use "sass:string";' +
								'@use "sass:color";' +
								'@use "sass:map";' +
								'@import "_colors"; ' +
								'@import "_variables"; ' +
								'@import "_breakpoints"; ' +
								'@import "_mixins"; ' +
								content
							);
						},
					},
				},
			],
		}
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

	// Transpile the blocks and `@woocommerce/*` package sources after removing
	// Storybook's default Babel rule above. Storybook 9 no longer transpiles the
	// blocks' TypeScript through its default preview rule.
	storybookConfig.module.rules.push( {
		test: /\.(j|t)sx?$/,
		// pnpm resolves workspace packages to their real path, so `@woocommerce/*`
		// source appears as `packages/js/<pkg>/src` (matched here) rather than the
		// `node_modules/@woocommerce/<pkg>` symlink.
		include:
			/[\/\\](?:(?:packages[\/\\]js|node_modules[\/\\]@woocommerce)[\/\\][^\/\\]+[\/\\]src|plugins[\/\\]woocommerce[\/\\]client[\/\\]blocks[\/\\](?:assets[\/\\]js|packages|storybook))[\/\\]/,
		loader: 'babel-loader',
	} );

	return storybookConfig;
};
