/**
 * Internal dependencies
 */
import { resolveAspectRatio } from '../utils';
import { ImageSizing } from '../types';

describe( 'resolveAspectRatio', () => {
	it( 'uses style.dimensions.aspectRatio when set', () => {
		expect(
			resolveAspectRatio(
				{
					style: { dimensions: { aspectRatio: '16/9' } },
					aspectRatio: '1/1',
					imageSizing: ImageSizing.THUMBNAIL,
				},
				'4/3'
			)
		).toBe( '16/9' );
	} );

	it( 'uses aspectRatio attribute when dimensions are not set', () => {
		expect(
			resolveAspectRatio(
				{
					aspectRatio: '3/5',
					imageSizing: ImageSizing.THUMBNAIL,
				},
				'1/1'
			)
		).toBe( '3/5' );
	} );

	it( 'falls back to store aspect ratio when no block override is set', () => {
		expect(
			resolveAspectRatio( { imageSizing: ImageSizing.THUMBNAIL }, '4/3' )
		).toBe( '4/3' );
	} );

	it( 'falls back to store aspect ratio when no block override is set and imageSizing is cropped', () => {
		expect(
			resolveAspectRatio( { imageSizing: ImageSizing.CROPPED }, '4/3' )
		).toBe( '4/3' );
	} );

	it( 'returns undefined when store aspect ratio is null (uncropped)', () => {
		expect(
			resolveAspectRatio( { imageSizing: ImageSizing.THUMBNAIL }, null )
		).toBeUndefined();
	} );

	it( 'returns undefined when imageSizing is not thumbnail', () => {
		expect(
			resolveAspectRatio( { imageSizing: ImageSizing.SINGLE }, '4/3' )
		).toBeUndefined();
	} );
} );
