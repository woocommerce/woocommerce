'use strict';

const {
	BUNDLED_PACKAGES,
	getNpmDistTagForWordPressVersion,
	getSupportedWordPressVersions,
	isBundledPackage,
	isWordPressPackage,
	resolveWordPressPackageSpec,
} = require( '../metadata' );

describe( 'metadata', () => {
	it( 'maps WordPress versions to npm dist-tags', () => {
		expect( getNpmDistTagForWordPressVersion( 'latest' ) ).toBe(
			'wp-latest'
		);
		expect( getNpmDistTagForWordPressVersion( 'latest-1' ) ).toBe(
			'wp-latest-1'
		);
		expect( getNpmDistTagForWordPressVersion( 'gutenberg' ) ).toBe(
			'latest'
		);
	} );

	it( 'builds npm package specs for WordPress version targets', () => {
		expect(
			resolveWordPressPackageSpec( '@wordpress/data', 'latest' )
		).toBe( '@wordpress/data@wp-latest' );
		expect(
			resolveWordPressPackageSpec( '@wordpress/data', 'latest-1' )
		).toBe( '@wordpress/data@wp-latest-1' );
		expect(
			resolveWordPressPackageSpec( '@wordpress/data', 'gutenberg' )
		).toBe( '@wordpress/data@latest' );
	} );

	it( 'rejects unsupported WordPress version targets', () => {
		expect( () => getNpmDistTagForWordPressVersion( '6.8' ) ).toThrow(
			/Unsupported WordPress version/
		);
		expect( () => getNpmDistTagForWordPressVersion( 'wp-latest' ) ).toThrow(
			/Unsupported WordPress version/
		);
		expect( () => getNpmDistTagForWordPressVersion( 'wp-6.8' ) ).toThrow(
			/Unsupported WordPress version/
		);
		expect( () => getNpmDistTagForWordPressVersion( 'nightly' ) ).toThrow(
			/Unsupported WordPress version/
		);
	} );

	it( 'lists supported WordPress versions', () => {
		expect( getSupportedWordPressVersions() ).toEqual( [
			'latest',
			'latest-1',
			'gutenberg',
		] );
	} );

	it( 'detects valid @wordpress package names', () => {
		expect( isWordPressPackage( '@wordpress/data' ) ).toBe( true );
		expect( isWordPressPackage( '@woocommerce/data' ) ).toBe( false );
		expect( isWordPressPackage( '@wordpress/data/build' ) ).toBe( false );
	} );

	it( 'detects packages bundled with WordPress but versioned outside the core target', () => {
		expect( BUNDLED_PACKAGES ).toContain( '@wordpress/icons' );
		expect( BUNDLED_PACKAGES ).toContain( '@wordpress/dataviews/wp' );
		expect( isBundledPackage( '@wordpress/icons' ) ).toBe( true );
		expect( isBundledPackage( '@wordpress/data' ) ).toBe( false );
	} );
} );
