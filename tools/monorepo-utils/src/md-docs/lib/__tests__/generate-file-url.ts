/**
 * External dependencies
 */
import path from 'path';

/**
 * Internal dependencies
 */
import { generateFileUrl } from '../generate-manifest';

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
} );
