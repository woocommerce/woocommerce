/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import React, { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Sortable } from '../sortable';

describe( 'Sortable', () => {
	it( 'should render the list items', () => {
		const { queryByText } = render(
			<Sortable>
				<div>Item 1</div>
				<div>Item 2</div>
			</Sortable>
		);
		expect( queryByText( 'Item 1' ) ).toBeInTheDocument();
		expect( queryByText( 'Item 2' ) ).toBeInTheDocument();
	} );
} );
