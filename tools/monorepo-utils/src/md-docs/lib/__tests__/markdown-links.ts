/**
 * External dependencies
 */
import path from 'path';

/**
 * Internal dependencies
 */
import { generateManifestFromDirectory } from '../generate-manifest';
import { processMarkdownLinks } from '../markdown-links';

describe( 'processMarkdownLinks', () => {
	const dir = path.join( __dirname, './fixtures/example-docs' );
	const rootDir = path.join( __dirname, './fixtures' );

	it( 'should add the correct relative links to a manifest', async () => {
		// generate the manifest from fixture directory
		const manifest = await generateManifestFromDirectory(
			rootDir,
			dir,
			'example-docs',
			'https://example.com',
			'https://example.com/edit'
		);

		const manifestWithLinks = await processMarkdownLinks(
			manifest,
			rootDir,
			dir,
			'example-docs'
		);

		const localDevelopmentPost =
			manifestWithLinks.categories[ 0 ].posts[ 0 ];

		expect(
			localDevelopmentPost.links[ './installation/install-plugin.md' ]
		).toBeDefined();

		const installationPost =
			manifestWithLinks.categories[ 0 ].categories[ 0 ].posts[ 0 ];

		expect(
			localDevelopmentPost.links[ './installation/install-plugin.md' ]
		).toEqual( installationPost.id );
	} );
} );
