/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Connect } from '../index.js';

jest.mock( '@wordpress/data', () => {
	const originalModule = jest.requireActual( '@wordpress/data' );
	return {
		...Object.keys( originalModule ).reduce( ( mocked, key ) => {
			try {
				mocked[ key ] = originalModule[ key ];
			} catch ( e ) {
				mocked[ key ] = jest.fn();
			}
			return mocked;
		}, {} ),
		useDispatch: jest.fn().mockReturnValue( {
			createNotice: jest.fn(),
		} ),
		useSelect: jest.fn().mockReturnValue( {} ),
	};
} );

describe( 'Rendering', () => {
	it( 'should render an abort button when the abort handler is provided', async () => {
		const { container } = render(
			<Connect createNotice={ () => {} } onAbort={ () => {} } />
		);

		const buttons = container.querySelectorAll( 'button' );

		expect( buttons.length ).toBe( 2 );
		expect( buttons[ 1 ].textContent ).toBe( 'Abort' );
	} );
} );
