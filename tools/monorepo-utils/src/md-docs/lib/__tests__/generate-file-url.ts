/**
 * External dependencies
 */
import path from 'path';

/**
 * Internal dependencies
 */
import { generateFileUrl } from '../generate-urls';

describe( 'generateFileUrl', () => {
	it( 'should generate a file url relative to the root directory provided', () => {
		const url = generateFileUrl(
			'https://example.com',
			path.join( __dirname, 'fixtures/example-docs' ),
			path.join( __dirname, 'fixtures/example-docs/get-started' ),
			path.join(
				__dirname,
				'fixtures/example-docs/get-started/local-development.md'
			)
		);

		expect( url ).toBe(
			'https://example.com/get-started/local-development.md'
		);
	} );

	it( 'should throw an error if relative paths are passed', () => {
		expect( () =>
			generateFileUrl(
				'https://example.com',
				'./example-docs',
				path.join( __dirname, 'fixtures/example-docs/get-started' ),
				path.join(
					__dirname,
					'fixtures/example-docs/get-started/local-development.md'
				)
			)
		).toThrow();

		expect( () =>
			generateFileUrl(
				'https://example.com',
				path.join( __dirname, 'fixtures/example-docs' ),
				'./get-started',
				path.join(
					__dirname,
					'fixtures/example-docs/get-started/local-development.md'
				)
			)
		).toThrow();

		expect( () =>
			generateFileUrl(
				'https://example.com',
				path.join( __dirname, 'fixtures/example-docs' ),
				path.join( __dirname, 'fixtures/example-docs/get-started' ),
				'./local-development.md'
			)
		).toThrow();
	} );
} );
