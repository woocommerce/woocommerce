import { describe, expect, it } from 'vitest';

/**
 * External dependencies
 */
import { render } from '@testing-library/react';
import React, { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { Sortable } from '../sortable';
const Item = ( { children } ) => <div>{ children }</div>;

describe( 'Sortable', () => {
	it( 'should render the list items', () => {
		const { queryByText } = render(
			<Sortable>
				<Item>Item 1</Item>
				<Item>Item 2</Item>
			</Sortable>
		);
		expect( queryByText( 'Item 1' ) ).toBeInTheDocument();
		expect( queryByText( 'Item 2' ) ).toBeInTheDocument();
	} );
} );
