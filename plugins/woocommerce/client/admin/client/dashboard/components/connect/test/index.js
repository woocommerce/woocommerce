/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Connect } from '../index.js';

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
