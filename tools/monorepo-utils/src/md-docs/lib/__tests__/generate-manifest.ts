/**
 * External dependencies
 */
import path from 'path';

/**
 * Internal dependencies
 */
import {
	generateManifestFromDirectory,
	generatePostId,
} from '../generate-manifest';

describe( 'generateManifest', () => {
	const dir = path.join( __dirname, './fixtures/example-docs' );
	const rootDir = path.join( __dirname, './fixtures' );

	it( 'should generate a manifest with the correct category structure', async () => {
		// generate the manifest from fixture directory
		const manifest = await generateManifestFromDirectory(
			rootDir,
			dir,
			'example-docs',
			'https://example.com',
			'https://example.com/edit'
		);

		const topLevelCategories = manifest.categories;

		expect( topLevelCategories[ 0 ].category_title ).toEqual(
			'Getting Started with WooCommerce'
		);
		expect( topLevelCategories[ 1 ].category_title ).toEqual(
			'Testing WooCommerce'
		);

		const subCategories = topLevelCategories[ 0 ].categories;

		expect( subCategories[ 1 ].category_title ).toEqual(
			'Troubleshooting Problems'
		);
	} );

	it( 'should generate a manifest with categories that contain all markdown files in a location as individual posts', async () => {
		// generate the manifest from fixture directory
		const manifest = await generateManifestFromDirectory(
			rootDir,
			dir,
			'example-docs',
			'https://example.com',
			'https://example.com/edit'
		);

		const topLevelCategories = manifest.categories;

		expect( topLevelCategories[ 1 ].category_title ).toEqual(
			'Testing WooCommerce'
		);

		const posts = topLevelCategories[ 1 ].posts;

		expect( posts ).toHaveLength( 2 );
		expect( posts[ 0 ].post_title ).toEqual( 'Unit Testing' );
		expect( posts[ 1 ].post_title ).toEqual( 'Integration Testing' );
	} );

	it( 'should create categories with titles where there is no index README', async () => {
		const manifest = await generateManifestFromDirectory(
			rootDir,
			dir,
			'example-docs',
			'https://example.com',
			'https://example.com/edit'
		);

		expect(
			manifest.categories[ 0 ].categories[ 0 ].category_title
		).toEqual( 'Installation' );
	} );

	it( 'should create post urls with the correct url', async () => {
		const manifest = await generateManifestFromDirectory(
			rootDir,
			dir,
			'example-docs',
			'https://example.com',
			'https://example.com/edit'
		);

		expect( manifest.categories[ 0 ].posts[ 0 ].url ).toEqual(
			'https://example.com/example-docs/get-started/local-development.md'
		);

		expect(
			manifest.categories[ 0 ].categories[ 0 ].posts[ 0 ].url
		).toEqual(
			'https://example.com/example-docs/get-started/installation/install-plugin.md'
		);
	} );

	it( 'should generate posts with stable IDs', async () => {
		const manifest = await generateManifestFromDirectory(
			rootDir,
			dir,
			'example-docs',
			'https://example.com',
			'https://example.com/edit'
		);

		expect( manifest.categories[ 0 ].posts[ 0 ].id ).toEqual(
			'29bce0a522cef4cd72aad4dd1c9ad5d0b6780704'
		);
	} );

	it( 'should create a hash for each manifest', async () => {
		const manifest = await generateManifestFromDirectory(
			rootDir,
			dir,
			'example-docs',
			'https://example.com',
			'https://example.com/edit'
		);

		expect( manifest.hash ).not.toBeUndefined();
	} );

	it( 'should generate edit_url when github is in the base url', async () => {
		const manifest = await generateManifestFromDirectory(
			rootDir,
			dir,
			'example-docs',
			'https://github.com',
			'https://github.com/edit'
		);

		expect( manifest.categories[ 0 ].posts[ 0 ].edit_url ).toEqual(
			'https://github.com/edit/example-docs/get-started/local-development.md'
		);
	} );
} );

describe( 'generatePostId', () => {
	it( 'should generate a stable ID for the same file', () => {
		const id1 = generatePostId(
			'get-started/local-development.md',
			'woodocs'
		);

		const id2 = generatePostId(
			'get-started/local-development.md',
			'woodocs'
		);

		expect( id1 ).toEqual( id2 );
	} );

	it( 'should generate a different ID for different prefixes', () => {
		const id1 = generatePostId(
			'get-started/local-development.md',
			'foodocs'
		);

		const id2 = generatePostId(
			'get-started/local-development.md',
			'woodocs'
		);

		expect( id1 ).not.toEqual( id2 );
	} );
} );
