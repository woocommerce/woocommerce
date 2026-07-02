/**
 * Internal dependencies
 */
import { resolveAspectRatio } from '../utils';

describe( 'resolveAspectRatio', () => {
	it( 'uses style.dimensions.aspectRatio when set', () => {
		expect(
			resolveAspectRatio(
				'1/1',
				{ dimensions: { aspectRatio: '16/9' } },
				'4/3'
			)
		).toBe( '16/9' );
	} );

	it( 'uses aspectRatio attribute when dimensions are not set', () => {
		expect( resolveAspectRatio( '3/5', undefined, '1/1' ) ).toBe( '3/5' );
	} );

	it( 'falls back to store aspect ratio when no block override is set', () => {
		expect( resolveAspectRatio( undefined, undefined, '4/3' ) ).toBe(
			'4/3'
		);
	} );

	it( 'returns undefined when store aspect ratio is null (uncropped)', () => {
		expect(
			resolveAspectRatio( undefined, undefined, null )
		).toBeUndefined();
	} );
} );
