/**
 * External dependencies
 */
import { getBlockSupport } from '@wordpress/blocks';

/**
 * Internal dependencies
 */
import { hasBackgroundImageSupport } from '../block-compatibility-warnings';

jest.mock( '@wordpress/blocks', () => ( { getBlockSupport: jest.fn() } ) );

describe( 'hasBackgroundImageSupport', () => {
	it.each( [
		[ 'supports background images', { backgroundImage: true }, true ],
		[ 'has background support without saying otherwise', {}, true ],
		[ 'turns background images off', { backgroundImage: false }, false ],
		[ 'has no background support', undefined, false ],
	] )( 'warns when the block %s: %s', ( _case, support, expected ) => {
		( getBlockSupport as jest.Mock ).mockReturnValue( support );

		expect( Boolean( hasBackgroundImageSupport( 'core/group' ) ) ).toBe(
			expected
		);
		expect( getBlockSupport ).toHaveBeenCalledWith(
			'core/group',
			'background'
		);
	} );
} );
