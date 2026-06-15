/**
 * Internal dependencies
 */
import { EmailStyles } from '../../../store';
import { getElementStyles, getHeadingElementStyles } from '../utils';

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
