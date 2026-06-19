/**
 * External dependencies
 */
import { render, fireEvent } from '@testing-library/react';
import { noop } from 'lodash';
import { createElement } from '@wordpress/element';

/**
 * Internal dependencies
 */
import { SearchListControl } from '../';

const list = [
	{ id: 1, name: 'Apricots' },
	{ id: 2, name: 'Clementine' },
	{ id: 3, name: 'Elderberry' },
	{ id: 4, name: 'Guava' },
	{ id: 5, name: 'Lychee' },
	{ id: 6, name: 'Mulberry' },
];

describe( 'SearchListControl', () => {
	test( 'should match options after changing search control', () => {
		const { getByLabelText, getAllByText } = render(
			<SearchListControl
				instanceId={ 1 }
				list={ list }
				search=""
				selected={ [] }
				onChange={ noop }
				debouncedSpeak={ noop }
			/>
		);

		fireEvent.change( getByLabelText( 'Search for items' ), {
			target: {
				value: 'berry',
			},
		} );
		expect( getAllByText( 'berry' ).length ).toBe( 2 );
	} );
} );
