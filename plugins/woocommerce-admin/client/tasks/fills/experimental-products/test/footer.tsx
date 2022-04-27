/**
 * External dependencies
 */
import { render } from '@testing-library/react';

/**
 * Internal dependencies
 */
import Footer from '../footer';

describe( 'Footer', () => {
	it( 'should render footer with two links', () => {
		const { queryAllByRole } = render( <Footer /> );
		expect( queryAllByRole( 'link' ) ).toHaveLength( 2 );
	} );
} );
