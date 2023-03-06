/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Card from '../index.js';

describe( 'Card', () => {
	it( 'should display title, description and children', () => {
		const { getByText } = render(
			<Card title="My fancy title" description="Making you feel good">
				<p>Hello World!</p>
			</Card>
		);

		expect( getByText( 'My fancy title' ) ).toBeInTheDocument();
		expect( getByText( 'Making you feel good' ) ).toBeInTheDocument();
		expect( getByText( 'Hello World!' ) ).toBeInTheDocument();
	} );
} );
