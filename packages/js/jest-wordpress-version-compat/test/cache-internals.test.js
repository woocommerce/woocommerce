'use strict';

const {
	createCacheManifestPackageJson,
	getCurrentCacheManifestPackageVersions,
} = require( '../src/cache-manifest' );
const { createCachePreparationPlan } = require( '../src/cache-plan' );
const { createPackageRegistry } = require( '../src/package-registry' );

describe( 'cache internals', () => {
	it( 'keeps cache manifest schema behind one interface', () => {
		const versionTarget = {
			distTag: 'wp-100.0',
			source: 'test-source',
			version: '100.0',
		};
		const packageVersions = {
			'@wordpress/data': '92.0.0',
		};
		const selectedPackages = [ '@wordpress/data' ];
		const manifest = createCacheManifestPackageJson( {
			packageVersions,
			selectedPackages,
			versionTarget,
			wpVersion: 'latest',
		} );

		expect(
			getCurrentCacheManifestPackageVersions( {
				manifest,
				selectedPackages,
				versionTarget,
				wpVersion: 'latest',
			} )
		).toBe( packageVersions );
		expect(
			getCurrentCacheManifestPackageVersions( {
				manifest,
				selectedPackages,
				versionTarget: { ...versionTarget, version: '101.0' },
				wpVersion: 'latest',
			} )
		).toBeUndefined();
	} );

	it( 'plans package installation from expected and installed versions', () => {
		const plan = createCachePreparationPlan( {
			installedPackageVersions: {
				'@wordpress/data': '92.0.0',
			},
			packageVersions: {
				'@wordpress/components': '92.0.0',
				'@wordpress/data': '92.0.0',
			},
		} );

		expect( plan ).toEqual( {
			cachePackages: [ '@wordpress/components', '@wordpress/data' ],
			missingPackages: [ '@wordpress/components' ],
			packageVersions: {
				'@wordpress/components': '92.0.0',
				'@wordpress/data': '92.0.0',
			},
			shouldInstall: true,
		} );
	} );

	it( 'resolves package versions and dependencies behind the registry interface', () => {
		const registry = createPackageRegistry( {
			install: jest.fn(),
			resolvePackageDependencies: jest.fn( ( packageName ) => {
				if ( packageName === '@wordpress/components' ) {
					return {
						'@wordpress/compose': '^7.0.0',
						'@wordpress/icons': '^10.0.0',
					};
				}

				return {};
			} ),
			resolvePackageVersion: jest.fn( () => '92.0.0' ),
			resolveTarget: jest.fn(),
		} );

		expect(
			registry.resolvePackageVersions( {
				packages: [ '@wordpress/components' ],
				versionTarget: {
					distTag: 'wp-100.0',
					source: 'test-source',
					version: '100.0',
				},
				wpVersion: 'latest',
			} )
		).toEqual( {
			'@wordpress/components': '92.0.0',
			'@wordpress/compose': '92.0.0',
		} );
	} );
} );
