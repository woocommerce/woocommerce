/**
 * Internal dependencies
 */
import { EmailStyles } from '../../../store';
import {
	getElementStyles,
	getFontFamilyFaces,
	getHeadingElementStyles,
} from '../utils';

describe( 'getElementStyles', () => {
	it( 'always returns typography and color objects, even when missing', () => {
		// `link` carrying only a color (as synced from a site theme) used to
		// crash consumers that destructure `typography`.
		const styles = {
			elements: { link: { color: { text: '#0000ff' } } },
		} as unknown as EmailStyles;

		const result = getElementStyles( styles, 'link' );

		expect( result.typography ).toEqual( {} );
		expect( result.color ).toEqual( { text: '#0000ff' } );
	} );

	it( 'returns empty typography and color when the element is absent', () => {
		const result = getElementStyles( {} as EmailStyles, 'button' );

		expect( result.typography ).toEqual( {} );
		expect( result.color ).toEqual( {} );
	} );

	it( 'falls back to empty typography for the text element', () => {
		// No top-level typography/color on the styles object.
		const result = getElementStyles( {} as EmailStyles, 'text' );

		expect( result.typography ).toEqual( {} );
		expect( result.color ).toEqual( {} );
	} );

	it( 'preserves existing text typography and color', () => {
		const styles = {
			typography: { fontFamily: 'serif' },
			color: { text: '#111111' },
		} as unknown as EmailStyles;

		const result = getElementStyles( styles, 'text' );

		expect( result.typography ).toEqual( { fontFamily: 'serif' } );
		expect( result.color ).toEqual( { text: '#111111' } );
	} );

	it( 'does not throw for the heading element when elements is undefined', () => {
		expect( () =>
			getElementStyles( {} as EmailStyles, 'heading' )
		).not.toThrow();

		const result = getElementStyles( {} as EmailStyles, 'heading' );
		expect( result.typography ).toEqual( {} );
	} );
} );

describe( 'getHeadingElementStyles', () => {
	it( 'does not throw when styles.elements is undefined', () => {
		expect( () =>
			getHeadingElementStyles( {} as EmailStyles, 'h2' )
		).not.toThrow();
	} );

	it( 'merges heading and heading-level typography when merge is true', () => {
		const styles = {
			elements: {
				heading: { typography: { fontWeight: '700' } },
				h2: { typography: { fontSize: '32px' } },
			},
		} as unknown as EmailStyles;

		const result = getHeadingElementStyles( styles, 'h2', true );

		expect( result.typography ).toEqual( {
			fontWeight: '700',
			fontSize: '32px',
		} );
	} );
} );

describe( 'getFontFamilyFaces', () => {
	const arial = {
		name: 'Arial',
		slug: 'arial',
		fontFamily: "Arial, 'Helvetica Neue', Helvetica, sans-serif",
	};
	const openSans = {
		name: 'Open Sans',
		slug: 'open-sans',
		fontFamily:
			"'open sans', 'helvetica neue', helvetica, arial, sans-serif",
	};
	const inter = {
		name: 'Inter',
		slug: 'inter',
		fontFamily: 'Inter, sans-serif',
		fontFace: [
			{ fontFamily: 'Inter', fontStyle: 'normal', fontWeight: '300' },
			{ fontFamily: 'Inter', fontStyle: 'normal', fontWeight: '600' },
		],
	};
	const fontFamilies = [ arial, openSans, inter ];
	const fontWeights = {
		arial: [ '400', '700' ],
		'open-sans': [ '300', '400', '500', '700' ],
		inter: [ '400', '700' ],
	};
	const regularAndBold = [
		{ fontStyle: 'normal', fontWeight: '400' },
		{ fontStyle: 'italic', fontWeight: '400' },
		{ fontStyle: 'normal', fontWeight: '700' },
		{ fontStyle: 'italic', fontWeight: '700' },
	];

	it( 'offers the weights the theme lists for the font, each with italic', () => {
		expect(
			getFontFamilyFaces(
				fontFamilies,
				fontWeights,
				arial.fontFamily,
				{}
			)
		).toEqual( regularAndBold );
		expect(
			getFontFamilyFaces(
				fontFamilies,
				fontWeights,
				openSans.fontFamily,
				{}
			)?.map( ( face ) => face.fontWeight )
		).toEqual( [ '300', '300', '400', '400', '500', '500', '700', '700' ] );
	} );

	it( 'prefers the font faces of a font that has them', () => {
		expect(
			getFontFamilyFaces(
				fontFamilies,
				fontWeights,
				inter.fontFamily,
				{}
			)
		).toEqual( inter.fontFace );
	} );

	it.each( [
		'var:preset|font-family|open-sans',
		'var(--wp--preset--font-family--open-sans)',
	] )( 'finds the font from a preset reference: %s', ( fontFamily ) => {
		expect(
			getFontFamilyFaces( fontFamilies, fontWeights, fontFamily, {} )
		).toHaveLength( 8 );
	} );

	it.each( [
		[ 'the font is not in the list', undefined, fontWeights ],
		[ 'the theme lists no weights for the font', arial.fontFamily, {} ],
		[ 'the theme lists no weights at all', arial.fontFamily, undefined ],
	] )( 'leaves the list to core when %s', ( _, fontFamily, weights ) => {
		expect(
			getFontFamilyFaces( fontFamilies, weights, fontFamily, {
				fontWeight: '300',
			} )
		).toBeUndefined();
	} );

	it( 'keeps a saved weight that the font does not offer', () => {
		expect(
			getFontFamilyFaces( fontFamilies, fontWeights, arial.fontFamily, {
				fontStyle: 'normal',
				fontWeight: '300',
			} )
		).toEqual( [
			...regularAndBold,
			{ fontStyle: 'normal', fontWeight: '300' },
		] );
	} );

	it( 'does not add a saved value the font already offers', () => {
		expect(
			getFontFamilyFaces( fontFamilies, fontWeights, arial.fontFamily, {
				fontStyle: 'italic',
				fontWeight: 700,
			} )
		).toEqual( regularAndBold );
	} );
} );
