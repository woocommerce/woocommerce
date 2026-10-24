/**
 * Internal dependencies
 */
import { getColorObject } from '../index';
import type { ColorPaletteOption, GradientPaletteOption } from '../types';

describe( 'getColorObject', () => {
	const getPalette = () =>
		[
			{ name: 'Color 7', slug: 'theme-7', color: '#FFFFFF' },
		] as ColorPaletteOption[] & GradientPaletteOption[];

	it( 'does not add a class to the theme palette entry it picks', () => {
		const palette = getPalette();

		const colorObject = getColorObject(
			palette,
			'#FFFFFF',
			'product-count-color'
		);

		expect( colorObject ).toEqual( {
			name: 'Color 7',
			slug: 'theme-7',
			color: '#FFFFFF',
			class: 'has-theme-7-product-count-color',
		} );
		expect( palette[ 0 ] ).toEqual( getPalette()[ 0 ] );
	} );

	it( 'finds a palette color by slug', () => {
		expect(
			getColorObject( getPalette(), 'theme-7', 'price-color' )
		).toMatchObject( {
			color: '#FFFFFF',
			class: 'has-theme-7-price-color',
		} );
	} );

	it( 'returns a custom color without a class', () => {
		expect(
			getColorObject( getPalette(), '#123456', 'price-color' )
		).toEqual( { color: '#123456', class: undefined } );
	} );

	it( 'returns nothing when no color is picked', () => {
		expect(
			getColorObject( getPalette(), undefined, 'price-color' )
		).toBeUndefined();
	} );
} );
