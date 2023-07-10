/**
 * External dependencies
 */
import path from 'path';

/**
 * Internal dependencies
 */
import { generateManifestFromDirectory } from '../generate-manifest';

describe( 'generateManifest', () => {
	const dir = path.join( __dirname, './fixtures/example-docs' );
	const rootDir = path.join( __dirname, './fixtures' );

	it( 'should generate a manifest with the correct category structure', async () => {
		// generate the manifest from fixture directory
		const manifest = await generateManifestFromDirectory(
			dir,
			rootDir,
			'example-docs',
			'https://example.com'
		);

		const topLevelCategories = manifest.categories;

		expect( topLevelCategories[ 0 ].title ).toEqual(
			'Getting Started with WooCommerce'
		);
		expect( topLevelCategories[ 1 ].title ).toEqual(
			'Testing WooCommerce'
		);

		const subCategories = topLevelCategories[ 0 ].categories;

		expect( subCategories[ 0 ].title ).toEqual(
			'Troubleshooting Problems'
		);
	} );

	it( 'should create post urls with the correct url', async () => {
		const manifest = await generateManifestFromDirectory(
			dir,
			rootDir,
			'example-docs',
			'https://example.com'
		);

		expect( manifest.categories[ 0 ].posts[ 0 ].url ).toEqual(
			'https://example.com/example-docs/get-started/local-development.md'
		);

		expect(
			manifest.categories[ 0 ].categories[ 0 ].posts[ 0 ].url
		).toEqual(
			'https://example.com/example-docs/get-started/troubleshooting/what-went-wrong.md'
		);
	} );
} );
