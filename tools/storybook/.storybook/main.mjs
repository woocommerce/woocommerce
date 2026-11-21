import { createRequire } from 'node:module';
import path from 'node:path';
import { fileURLToPath } from 'node:url';
import { mergeConfig, defaultClientConditions } from 'vite';
import { storybookVite } from '@woocommerce/rolldown-build/storybook';

const require = createRequire( import.meta.url );
const HERE = path.dirname( fileURLToPath( import.meta.url ) );
const REPO = path.resolve( HERE, '../../..' );
const ADMIN = path.join( REPO, 'plugins/woocommerce/client/admin' );
const SETTINGS_UI = path.join( REPO, 'packages/js/settings-ui' );
const settingsRequire = createRequire(
	path.join( SETTINGS_UI, 'package.json' )
);
const packageDir = ( name, from ) =>
	path.dirname(
		createRequire( path.join( from, 'package.json' ) ).resolve(
			`${ name }/package.json`
		)
	);
const dataViewsDir = packageDir( '@wordpress/dataviews', SETTINGS_UI );
const dataViewsUiDir = packageDir( '@wordpress/ui', dataViewsDir );
// DataViews' /wp build leaves its third-party imports to its own package
// graph, as in the admin build (client/admin/bin/rolldown/config.mjs).
const dataViewsRoots = [
	...new Set(
		[
			dataViewsDir,
			packageDir( '@wordpress/components', dataViewsDir ),
			packageDir( '@wordpress/compose', dataViewsDir ),
			packageDir( '@wordpress/data', dataViewsDir ),
			dataViewsUiDir,
			packageDir( '@wordpress/theme', dataViewsUiDir ),
		].map( ( dir ) => path.resolve( dir, '../..' ) )
	),
];
const dataViewsWpEntry = settingsRequire.resolve( '@wordpress/dataviews/wp' );
const sideEffectFiles = new Set( [
	dataViewsWpEntry,
	path.join( SETTINGS_UI, 'src/dataform-runtime.ts' ),
] );

const dataViewsResolve = {
	name: 'woocommerce:dataviews-resolve',
	enforce: 'pre',
	async resolveId( source, importer, options ) {
		if ( /^[.\0]/.test( source ) && importer !== dataViewsWpEntry ) {
			const resolved = await this.resolve( source, importer, {
				...options,
				skipSelf: true,
			} );
			return resolved && sideEffectFiles.has( resolved.id )
				? { ...resolved, moduleSideEffects: true }
				: null;
		}
		if ( source === '@wordpress/dataviews/wp' ) {
			const resolved = await this.resolve( source, importer, {
				...options,
				skipSelf: true,
			} );
			return resolved && { ...resolved, moduleSideEffects: true };
		}
		if ( importer !== dataViewsWpEntry || /^[./]/.test( source ) ) {
			return null;
		}
		for ( const root of dataViewsRoots ) {
			const resolved = await this.resolve(
				source,
				path.join( root, '..', 'index.js' ),
				{ ...options, skipSelf: true }
			);
			if ( resolved ) {
				return resolved;
			}
		}
		return null;
	},
};

const wpComponentsCss = path.join(
	path.dirname( require.resolve( '@wordpress/components/package.json' ) ),
	'build-style/style.css'
);

// components/src/calendar/style.scss writes its plain CSS imports relative
// to src/style.scss, which webpack accepted. Vite rebases them to the partial.
const COMPONENTS_SRC = path.join( REPO, 'packages/js/components/src' );
const calendarCssAliases = [
	{
		find: /^(?:.*\/)?calendar\/calendar\/((?:begin|end)-rtl-ignore\.css)$/,
		replacement: path.join( COMPONENTS_SRC, 'calendar/$1' ),
	},
	{
		find: /^(?:.*\/)?node_modules\/react-dates\/lib\/css\/_datepicker\.css$/,
		replacement: createRequire(
			path.join( COMPONENTS_SRC, '../package.json' )
		).resolve( 'react-dates/lib/css/_datepicker.css' ),
	},
];

const staticDirs = [
	// WordPress admin CSS from import-wp-css-storybook.sh.
	{ from: '../wordpress/css', to: 'wordpress/css' },
	{
		from: path.relative( HERE, wpComponentsCss ),
		to: 'wordpress/css/components.css',
	},
	{
		from: '../../../plugins/woocommerce/client/admin/client',
		to: 'main/plugins/woocommerce/client/admin/client',
	},
];
if ( process.env.NODE_ENV && process.env.NODE_ENV === 'production' ) {
	// Add WooCommerce Blocks Storybook for build process.
	staticDirs.push( {
		from: '../../../plugins/woocommerce/client/blocks/storybook/dist',
		to: '/assets/woocommerce-blocks',
	} );
}
export default {
	stories: [
		// Introductory documentation
		'../stories/**/*.mdx',
		// WooCommerce Admin / @woocommerce/components components
		'../../../packages/js/components/src/**/stories/*.story.@(js|tsx)',
		// WooCommerce Admin / @woocommerce/experimental components
		'../../../packages/js/experimental/src/**/stories/*.story.@(js|tsx)',
		// WooCommerce Admin / @woocommerce/onboarding components
		'../../../packages/js/onboarding/src/**/stories/*.story.@(js|tsx)',
		'../../../plugins/woocommerce/client/admin/client/**/stories/*.story.@(js|tsx)',
	],
	refs: ( config, { configType } ) => {
		if ( configType === 'DEVELOPMENT' ) {
			// WooCommerce Blocks gets automatically on port 6006 run when you run pnpm --filter=@woocommerce/storybook watch:build
			return {
				'woocommerce-blocks': {
					expanded: false,
					title: 'Blocks',
					url: 'http://localhost:6006',
				},
			};
		}

		let pathPrefix = (
			process.env.STORYBOOK_COMPOSITION_PATH_PREFIX ?? ''
		).trim();
		if ( pathPrefix && ! pathPrefix.startsWith( '/' ) ) {
			pathPrefix = '/' + pathPrefix;
		}
		return {
			'woocommerce-blocks': {
				expanded: false,
				title: 'Blocks',
				url: pathPrefix + '/assets/woocommerce-blocks',
			},
		};
	},
	addons: [
		'@storybook/addon-docs',
		'@storybook/addon-a11y',
		'@storybook/addon-links',
	],

	typescript: {
		reactDocgen: 'react-docgen-typescript',
	},

	staticDirs,

	viteFinal: ( config ) => {
		const shared = storybookVite( 'admin', {
			// `storybook-rtl` previews the styles flipped.
			rtl: process.env.USE_RTL_STYLE === 'true',
		} );
		return mergeConfig( config, {
			...shared,
			plugins: [ ...shared.plugins, dataViewsResolve ],
			resolve: {
				alias: [
					...calendarCssAliases,
					{
						find: /^~\//,
						replacement: path.join( ADMIN, 'client/' ),
					},
					{
						find: /^@woocommerce\/settings$/,
						replacement: path.join( HERE, '../setting.mock.js' ),
					},
				],
				// Load `@woocommerce/*` packages from source, like the build.
				conditions: [ 'wc-source', ...defaultClientConditions ],
				dedupe: [ 'react', 'react-dom' ],
			},
		} );
	},

	previewHead: ( head ) => `
		${ head }

		<style>
			/* Use system font, consistent with WordPress core (wp-admin) */
			body {
				font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto,
					Oxygen-Sans, Ubuntu, Cantarell, 'Helvetica Neue', sans-serif;
			}
		</style>
	`,

	previewBody: ( body ) => `
	<div id="wpwrap">
		<div class="woocommerce-layout woocommerce-admin-page">
			${ body }

	`,

	framework: {
		name: '@storybook/react-vite',
		options: {},
	},
};
