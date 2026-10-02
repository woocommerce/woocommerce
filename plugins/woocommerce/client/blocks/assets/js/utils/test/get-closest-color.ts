/**
 * Internal dependencies
 */
import { getClosestColor } from '../get-closest-color';

describe( 'getClosestColor', () => {
	let colors: Map< Element, string >;

	const nest = ( parentColor: string, childColor: string ) => {
		const parent = document.createElement( 'div' );
		const child = document.createElement( 'span' );
		parent.appendChild( child );
		colors.set( parent, parentColor );
		colors.set( child, childColor );
		return child;
	};

	beforeEach( () => {
		colors = new Map();
		jest.spyOn( window, 'getComputedStyle' ).mockImplementation(
			( element ) =>
				( {
					backgroundColor:
						colors.get( element ) ?? 'rgba(0, 0, 0, 0)',
				} ) as CSSStyleDeclaration
		);
	} );

	afterEach( () => {
		jest.restoreAllMocks();
	} );

	it.each( [
		[ 'rgb(12, 34, 56)', 'rgb(12, 34, 56)' ],
		[ 'rgba(12, 34, 56, 0.5)', 'rgb(12, 34, 56)' ],
	] )( 'returns %s as %s, as before', ( computed, expected ) => {
		expect(
			getClosestColor(
				nest( 'rgb(1, 1, 1)', computed ),
				'backgroundColor'
			)
		).toBe( expected );
	} );

	// Values from the bug report: the old parser turned these into rgb(0, 999994, 0) and rgb(0, 94, 0).
	it.each( [
		'oklab(0.999994 0.0000455678 0.0000200868 / 0.75)',
		'color(srgb 0.94 0.93 0.89 / 0.5)',
		'oklch(0.7 0.1 250)',
	] )( 'returns the modern color %s unchanged', ( computed ) => {
		expect(
			getClosestColor(
				nest( 'rgb(1, 1, 1)', computed ),
				'backgroundColor'
			)
		).toBe( computed );
	} );

	it.each( [
		'transparent',
		'rgba(0, 0, 0, 0)',
		'rgba(255, 255, 255, 0)',
		'oklab(0 0 0 / 0)',
	] )(
		'skips the fully transparent %s and uses the parent color',
		( computed ) => {
			expect(
				getClosestColor(
					nest( 'rgb(9, 8, 7)', computed ),
					'backgroundColor'
				)
			).toBe( 'rgb(9, 8, 7)' );
		}
	);

	it( 'returns null when no element has a color', () => {
		expect(
			getClosestColor(
				nest( 'transparent', 'transparent' ),
				'backgroundColor'
			)
		).toBeNull();
	} );
} );
