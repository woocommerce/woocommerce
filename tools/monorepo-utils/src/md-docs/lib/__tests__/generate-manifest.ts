/**
 * External dependencies
 */
import path from 'path';
import fs from 'fs';

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

	it( 'should exclude files and folders matching patterns in .manifestignore', async () => {
		// generate the manifest from fixture directory
		const manifest = await generateManifestFromDirectory(
			rootDir,
			dir,
			'example-docs',
			'https://example.com',
			'https://example.com/edit'
		);

		const topLevelCategories = manifest.categories;

		expect( topLevelCategories ).toHaveLength( 2 );
		expect( topLevelCategories[ 0 ].posts ).toHaveLength( 1 );
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

	it( 'should create a hash for each post in a manifest', async () => {
		const manifest = await generateManifestFromDirectory(
			rootDir,
			dir,
			'example-docs',
			'https://example.com',
			'https://example.com/edit'
		);

		const topLevelCategories = manifest.categories;

		const posts = [
			...topLevelCategories[ 0 ].posts,
			...topLevelCategories[ 0 ].categories[ 0 ].posts,
			...topLevelCategories[ 0 ].categories[ 1 ].posts,
			...topLevelCategories[ 1 ].posts,
		];

		posts.forEach( ( post ) => {
			expect( post.hash ).not.toBeUndefined();
		} );
	} );

	it( 'should update a post hash and manifest hash when content is updated', async () => {
		const manifest = await generateManifestFromDirectory(
			rootDir,
			dir,
			'example-docs',
			'https://example.com',
			'https://example.com/edit'
		);
		const post = manifest.categories[ 0 ].posts[ 0 ];
		const originalPostHash = post.hash;
		const originalManifestHash = manifest.hash;

		// Confirm hashes are not undefined
		expect( originalPostHash ).not.toBeUndefined();
		expect( originalManifestHash ).not.toBeUndefined();

		// Update the file content of the corresponding post
		const filePath = path.join( dir, 'get-started/local-development.md' );
		const fileContent = fs.readFileSync( filePath, 'utf-8' );
		const updatedFileContent = fileContent + '\n\n<!-- updated -->';
		fs.writeFileSync( filePath, updatedFileContent );

		// Generate a new manifest
		const nextManifest = await generateManifestFromDirectory(
			rootDir,
			dir,
			'example-docs',
			'https://example.com',
			'https://example.com/edit'
		);
		const nextPost = nextManifest.categories[ 0 ].posts[ 0 ];
		const nextPostHash = nextPost.hash;
		const nextManifestHash = nextManifest.hash;

		// Confirm hashes are newly created.
		expect( nextPostHash ).not.toEqual( originalPostHash );
		expect( nextManifestHash ).not.toEqual( originalManifestHash );

		// Reset the file content
		fs.writeFileSync( filePath, fileContent );
	} );

	it( 'should not update a post hash and manifest hash when content is unchanged', async () => {
		const manifest = await generateManifestFromDirectory(
			rootDir,
			dir,
			'example-docs',
			'https://example.com',
			'https://example.com/edit'
		);
		const post = manifest.categories[ 0 ].posts[ 0 ];
		const originalPostHash = post.hash;
		const originalManifestHash = manifest.hash;

		// Confirm hashes are not undefined
		expect( originalPostHash ).not.toBeUndefined();
		expect( originalManifestHash ).not.toBeUndefined();

		// Generate a new manifest
		const nextManifest = await generateManifestFromDirectory(
			rootDir,
			dir,
			'example-docs',
			'https://example.com',
			'https://example.com/edit'
		);
		const nextPost = nextManifest.categories[ 0 ].posts[ 0 ];
		const nextPostHash = nextPost.hash;
		const nextManifestHash = nextManifest.hash;

		// Confirm hashes are newly created.
		expect( nextPostHash ).toEqual( originalPostHash );
		expect( nextManifestHash ).toEqual( originalManifestHash );
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
