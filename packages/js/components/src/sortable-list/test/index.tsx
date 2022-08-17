/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import React, { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SortableList } from '../sortable-list';

describe( 'SortableList', () => {
	it( 'should render the list items', () => {
		const { queryByText } = render(
			<SortableList>
				<div>Item 1</div>
				<div>Item 2</div>
			</SortableList>
		);
		expect( queryByText( 'Item 1' ) ).toBeInTheDocument();
		expect( queryByText( 'Item 2' ) ).toBeInTheDocument();
	} );
} );
