/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import React, { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Handle } from '../handle';
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

	it( 'should render the list handles', () => {
		const { getByLabelText } = render(
			<SortableList>
				<div>Item 1</div>
			</SortableList>
		);
		expect( getByLabelText( 'Move this item' ) ).toBeInTheDocument();
	} );

	it( 'should render the custom list handles', () => {
		const { queryAllByLabelText } = render(
			<SortableList shouldRenderHandles={ false }>
				<div>
					<Handle>Custom handle</Handle>
					Item 1
				</div>
			</SortableList>
		);

		const handles = queryAllByLabelText( 'Move this item' );

		expect( handles.length ).toBe( 1 );
		expect( handles[ 0 ].textContent ).toBe( 'Custom handle' );
	} );
} );
