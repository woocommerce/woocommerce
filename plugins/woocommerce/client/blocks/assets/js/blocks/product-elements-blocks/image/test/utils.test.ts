/**
 * Internal dependencies
 */
import { resolveAspectRatio } from '../utils';
import { ImageSizing } from '../types';

describe( 'resolveAspectRatio', () => {
	it( 'uses style.dimensions.aspectRatio when set', () => {
		expect(
			resolveAspectRatio(
				{ dimensions: { aspectRatio: '16/9' } },
				'1/1',
				'4/3',
				ImageSizing.THUMBNAIL
			)
		).toBe( '16/9' );
	} );

	it( 'uses aspectRatio attribute when dimensions are not set', () => {
		expect(
			resolveAspectRatio( undefined, '3/5', '1/1', ImageSizing.THUMBNAIL )
		).toBe( '3/5' );
	} );

	it( 'falls back to store aspect ratio when no block override is set', () => {
		expect(
			resolveAspectRatio(
				undefined,
				undefined,
				'4/3',
				ImageSizing.THUMBNAIL
			)
		).toBe( '4/3' );
	} );

	it( 'falls back to store aspect ratio when no block override is set and imageSizing is cropped', () => {
		expect(
			resolveAspectRatio(
				undefined,
				undefined,
				'4/3',
				ImageSizing.CROPPED
			)
		).toBe( '4/3' );
	} );

	it( 'returns undefined when store aspect ratio is null (uncropped)', () => {
		expect(
			resolveAspectRatio(
				undefined,
				undefined,
				null,
				ImageSizing.THUMBNAIL
			)
		).toBeUndefined();
	} );

	it( 'returns undefined when imageSizing is not thumbnail', () => {
		expect(
			resolveAspectRatio(
				undefined,
				undefined,
				'4/3',
				ImageSizing.SINGLE
			)
		).toBeUndefined();
	} );
} );
