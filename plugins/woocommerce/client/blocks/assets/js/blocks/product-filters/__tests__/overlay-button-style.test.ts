/**
 * Internal dependencies
 */
import { getOverlayButtonStyle } from '../utils/overlay-button-style';
import type { BlockAttributes } from '../types';

describe( 'getOverlayButtonStyle', () => {
	it.each( [
		[ 'no stored style', {}, 'plain' ],
		[ 'an unknown style', { overlayButtonStyle: 'ghost' }, 'plain' ],
		[ 'plain', { overlayButtonStyle: 'plain' }, 'plain' ],
		[ 'button', { overlayButtonStyle: 'button' }, 'button' ],
	] )( 'returns %s as %s', ( _label, attributes, expected ) => {
		expect( getOverlayButtonStyle( attributes as BlockAttributes ) ).toBe(
			expected
		);
	} );
} );
