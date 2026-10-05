/**
 * External dependencies
 */
import path from 'node:path';
import { existsSync } from 'node:fs';
import { createRequire } from 'node:module';
import { fileURLToPath } from 'node:url';
import { transformAsync } from '@babel/core';
import { defineConfig, mergeConfig } from 'vitest/config';

const directory = path.dirname( fileURLToPath( import.meta.url ) );
const helperRequire = createRequire( import.meta.url );
const monorepo = path.resolve( directory, '../../..' );

/**
 * Shared unit-test configuration for workspace source and React components.
 *
 * @param {string} root Project directory.
 * @param {Object} options Project-specific aliases, setup and environment.
 * @return {Object} Vitest configuration.
 */
export function createTestConfig( root, options = {} ) {
	const projectRequire = createRequire( path.join( root, 'package.json' ) );
	const projectManifest = projectRequire( './package.json' );
	const componentsRequire = createRequire(
		path.join( directory, '../components/package.json' )
	);
	const aliases = [];

	for ( const name of [
		'react',
		'react-dom',
		'@wordpress/private-apis',
		'@wordpress/element',
		'@wordpress/data',
		'@wordpress/core-data',
		'@wordpress/components',
		'@wordpress/html-entities',
		'@wordpress/notices',
		'@wordpress/blocks',
		'@wordpress/block-editor',
		'@wordpress/editor',
		'@wordpress/keyboard-shortcuts',
		'@wordpress/patterns',
		'@wordpress/rich-text',
		'@wordpress/compose',
		'@wordpress/keycodes',
		'@wordpress/escape-html',
		'@wordpress/data-controls',
		'@wordpress/commands',
		'@wordpress/api-fetch',
		'@testing-library/react',
	] ) {
		try {
			let entry;
			let resolver = helperRequire;
			if (
				name !== '@testing-library/react' &&
				( projectManifest.dependencies?.[ name ] ||
					projectManifest.devDependencies?.[ name ] ||
					! name.startsWith( '@wordpress/' ) )
			)
				resolver = projectRequire;
			try {
				entry = resolver.resolve( name );
			} catch {
				entry = helperRequire.resolve( name );
			}
			const esmEntry = entry
				.replace( '/build/', '/build-module/' )
				.replace( /\.cjs$/, '.mjs' );
			const esm = existsSync( esmEntry ) ? esmEntry : entry;
			aliases.push( {
				find: new RegExp( `^${ name }$` ),
				replacement: esm,
			} );
		} catch {
			// Some projects do not depend on all WordPress packages.
		}
	}
	for ( const name of [
		'@automattic/tour-kit',
		'@automattic/components',
		'@automattic/viewport',
		'@automattic/viewport-react',
		'@wordpress/react-i18n',
		'wordpress-components',
		'wordpress-components-slotfill',
	] ) {
		try {
			let resolver = componentsRequire;
			if ( name.startsWith( 'wordpress-' ) ) resolver = projectRequire;
			else if ( name !== '@automattic/tour-kit' )
				resolver = createRequire(
					componentsRequire.resolve( '@automattic/tour-kit' )
				);
			const entry = resolver.resolve( name );
			const esmEntry = entry
				.replace( '/dist/cjs/', '/dist/esm/' )
				.replace( '/build/', '/build-module/' );
			aliases.push( {
				find: new RegExp( `^${ name }$` ),
				replacement: existsSync( esmEntry ) ? esmEntry : entry,
			} );
		} catch {}
	}
	const config = mergeConfig(
		defineConfig( {
			root,
			plugins: [
				{
					name: 'woocommerce-test-jsx',
					enforce: 'pre',
					async transform( code, id ) {
						if (
							! /\.[jt]sx?$/.test( id ) ||
							id.includes( '/node_modules/' )
						) {
							return;
						}
						return transformAsync( code, {
							filename: id,
							babelrc: false,
							configFile: false,
							plugins: [
								( { types } ) => ( {
									visitor: {
										Program( program ) {
											let hasJSX = false;
											program.traverse( {
												JSXElement() {
													hasJSX = true;
												},
												JSXFragment() {
													hasJSX = true;
												},
											} );
											if ( hasJSX )
												program.unshiftContainer(
													'body',
													types.importDeclaration(
														[
															types.importNamespaceSpecifier(
																types.identifier(
																	'__wcReact'
																)
															),
														],
														types.stringLiteral(
															'react'
														)
													)
												);
										},
									},
								} ),
							],
							presets: [
								[
									helperRequire.resolve(
										'@babel/preset-react'
									),
									{
										runtime: 'classic',
										pragma: '__wcReact.createElement',
										pragmaFrag: '__wcReact.Fragment',
									},
								],
								...( /\.tsx?$/.test( id )
									? [
											[
												helperRequire.resolve(
													'@babel/preset-typescript'
												),
												{
													allExtensions: true,
													isTSX: id.endsWith(
														'.tsx'
													),
												},
											],
									  ]
									: [] ),
							],
							sourceMaps: true,
						} );
					},
				},
			],
			resolve: {
				conditions: [ 'wc-source' ],
				mainFields: [ 'module', 'main' ],
				dedupe: [ 'react', 'react-dom' ],
				alias: [
					{
						find: /^@woocommerce\/monorepo-utils\/(?:src\/)?(.*)$/,
						replacement: path.join(
							monorepo,
							'tools/monorepo-utils/src/$1'
						),
					},
					{
						find: /^.*\.(jpg|jpeg|png|gif|eot|otf|webp|svg|ttf|woff|woff2|mp4|webm|wav|mp3|m4a|aac|oga)$/,
						replacement: path.join(
							directory,
							'src/mocks/static.js'
						),
					},
					{
						find: /^.*\.(scss|css)$/,
						replacement: path.join(
							directory,
							'src/mocks/style-mock.js'
						),
					},
					{
						find: /^vitest$/,
						replacement: path.join(
							path.dirname(
								createRequire(
									path.join( process.cwd(), 'package.json' )
								).resolve( 'vitest/package.json' )
							),
							'dist/index.js'
						),
					},
					{
						find: /^tinymce$/,
						replacement: path.join(
							directory,
							'src/mocks/tinymce.js'
						),
					},
					{
						find: /^@woocommerce\/settings$/,
						replacement: path.join(
							directory,
							'src/mocks/woocommerce-settings.js'
						),
					},
					{
						find: /^@woocommerce\/tracks$/,
						replacement: path.join(
							directory,
							'src/mocks/woocommerce-tracks.js'
						),
					},
					{
						find: /^@woocommerce\/internal-js-tests$/,
						replacement: path.join(
							directory,
							'src/util/index.js'
						),
					},
					{
						find: /^@woocommerce\/([^/]+)\/(?:src|build|build-module|build-types)\/(.+)$/,
						replacement: path.join( directory, '../$1/src/$2' ),
					},
					{
						find: /^@woocommerce\/([^/]+)\/(.+)$/,
						replacement: path.join( directory, '../$1/src/$2' ),
					},
					{
						find: /^@woocommerce\/([^/]+)$/,
						replacement: path.join( directory, '../$1/src' ),
					},
					{
						find: /^~\/(.*)/,
						replacement: path.join(
							monorepo,
							'plugins/woocommerce/client/admin/client/$1'
						),
					},
					...aliases,
				],
			},
			test: {
				globals: false,
				testTimeout: 15000,
				environmentOptions: { jsdom: { url: 'http://localhost/' } },
				fsModuleCache: true,
				deps: {
					optimizer: {
						client: { enabled: false },
						ssr: { enabled: false },
					},
				},
				environment: 'jsdom',
				include: [
					'**/__tests__/**/*.{js,jsx,ts,tsx}',
					'**/test/*.{js,jsx,ts,tsx}',
					'**/*.{test,spec}.{js,jsx,ts,tsx}',
				],
				exclude: [
					'**/node_modules/**',
					'**/vendor/**',
					'**/build*/**',
					'**/dist/**',
					'**/*.d.ts',
					'**/fixtures/**',
					'**/tests/e2e/**',
				],
				restoreMocks: true,
				setupFiles: [
					path.join( directory, 'src/setup-window-globals.js' ),
					path.join( directory, 'src/setup-globals.js' ),
					path.join(
						directory,
						'src/setup-react-testing-library.js'
					),
				],
				sequence: { setupFiles: 'list' },
				server: {
					deps: {
						inline: [
							/@wordpress\//,
							/@woocommerce\//,
							/@automattic\//,
							/@testing-library\/jest-dom/,
						],
					},
				},
				coverage: {
					provider: 'v8',
					exclude: [
						'**/test/**',
						'**/__tests__/**',
						'**/tests/**',
						'**/*.d.ts',
					],
				},
			},
		} ),
		options
	);
	for ( const key of [ 'include', 'setupFiles' ] ) {
		if ( options.test?.[ key ] ) config.test[ key ] = options.test[ key ];
	}
	return config;
}
