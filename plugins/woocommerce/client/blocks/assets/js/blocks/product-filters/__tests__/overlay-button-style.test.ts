/**
 * Internal dependencies
 */
import { getOverlayButtonStyle } from '../utils/overlay-button-style';
import type { BlockAttributes } from '../types';

describe( 'getOverlayButtonStyle', () => {
	it.each( [
		[ 'no stored style', {}, 'plain' ],
		[
			'a style that is not a safe class name',
			{ overlayButtonStyle: 'a"><b' },
			'plain',
		],
		[ 'a non-string value', { overlayButtonStyle: 3 }, 'plain' ],
		[ 'plain', { overlayButtonStyle: 'plain' }, 'plain' ],
		[ 'outline', { overlayButtonStyle: 'outline' }, 'outline' ],
		[ 'fill', { overlayButtonStyle: 'fill' }, 'fill' ],
		[
			'a theme style',
			{ overlayButtonStyle: 'button-contrast' },
			'button-contrast',
		],
	] )( 'returns %s as %s', ( _label, attributes, expected ) => {
		expect( getOverlayButtonStyle( attributes as BlockAttributes ) ).toBe(
			expected
		);
	} );
} );
