/**
 * Internal dependencies
 */
import { previewCart } from '../cart';

/**
 * Verifies that the cart preview's `items` entries carry `is_canonical_line`
 * with the same value a real Store API cart response emits for a plain line,
 * while `cross_sells` entries — product responses, not cart lines — do not
 * carry the field at all.
 */
describe( 'previewCart', () => {
	it( 'declares is_canonical_line: true on every items entry', () => {
		expect( previewCart.items.length ).toBeGreaterThan( 0 );

		previewCart.items.forEach( ( item ) => {
			expect( item.is_canonical_line ).toBe( true );
		} );
	} );

	it( 'does not declare is_canonical_line on any cross_sells entry', () => {
		expect( previewCart.cross_sells.length ).toBeGreaterThan( 0 );

		previewCart.cross_sells.forEach( ( product ) => {
			expect( 'is_canonical_line' in product ).toBe( false );
		} );
	} );
} );
