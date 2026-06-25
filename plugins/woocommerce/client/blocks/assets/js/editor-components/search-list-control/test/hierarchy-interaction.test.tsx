/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { SearchListControl } from '..';
import type { SearchListItem } from '../types';

const hierarchicalList = [
	{ id: 1, name: 'Apricots', parent: 0, value: 'apricots' },
	{ id: 2, name: 'Clementine', parent: 1, value: 'clementine' },
	{ id: 3, name: 'Elderberry', parent: 1, value: 'elderberry' },
	{ id: 4, name: 'Guava', parent: 3, value: 'guava' },
	{ id: 5, name: 'Lychee', parent: 0, value: 'lychee' },
	{ id: 6, name: 'Mulberry', parent: 0, value: 'mulberry' },
] as SearchListItem[];

const renderHierarchicalControl = ( {
	selected = [] as SearchListItem[],
	onChange = jest.fn(),
} = {} ) =>
	render(
		<SearchListControl
			isHierarchical
			isSingle={ false }
			isCompact
			list={ hierarchicalList }
			selected={ selected }
			onChange={ onChange }
		/>
	);

const getTreeItemByName = ( container: HTMLElement, name: string ) =>
	Array.from( container.querySelectorAll( '[role="treeitem"]' ) ).find(
		( element ) => element.textContent.includes( name )
	);

const expandCategory = ( container: HTMLElement, name: string ) => {
	const treeItem = getTreeItemByName( container, name );
	if ( treeItem ) {
		fireEvent.click( treeItem );
	}
};

describe( 'SearchListControl hierarchy interactions', () => {
	test( 'keeps ancestor branches open when expanding nested categories', () => {
		const { container } = renderHierarchicalControl();

		expandCategory( container, 'Apricots' );
		expandCategory( container, 'Elderberry' );

		expect(
			screen.getByRole( 'checkbox', { name: 'Guava' } )
		).toBeInTheDocument();
	} );

	test( 'selects a leaf category at depth greater than one', () => {
		const onChange = jest.fn();
		const { container } = renderHierarchicalControl( { onChange } );

		expandCategory( container, 'Apricots' );
		expandCategory( container, 'Elderberry' );
		fireEvent.click( screen.getByRole( 'checkbox', { name: 'Guava' } ) );

		expect( onChange ).toHaveBeenCalledWith(
			expect.arrayContaining( [
				expect.objectContaining( { id: 4, name: 'Guava' } ),
			] )
		);
	} );

	test( 'selects a parent category and all descendants when its checkbox is checked', () => {
		const onChange = jest.fn();
		const { container } = renderHierarchicalControl( { onChange } );

		expandCategory( container, 'Apricots' );
		fireEvent.click( screen.getByRole( 'checkbox', { name: 'Apricots' } ) );

		const selectedIds = onChange.mock.calls[ 0 ][ 0 ].map(
			( item: SearchListItem ) => item.id
		);
		expect( selectedIds ).toEqual( [ 1, 2, 3, 4 ] );
	} );

	test( 'deselects a parent category and all descendants when its checkbox is unchecked', () => {
		const onChange = jest.fn();
		const selected = hierarchicalList.filter( ( { id } ) =>
			[ 1, 2, 3, 4 ].includes( Number( id ) )
		);
		const { container } = renderHierarchicalControl( {
			onChange,
			selected,
		} );

		expandCategory( container, 'Apricots' );
		fireEvent.click( screen.getByRole( 'checkbox', { name: 'Apricots' } ) );

		expect( onChange ).toHaveBeenCalledWith( [] );
	} );

	test( 'shows an indeterminate parent when only a descendant is selected', () => {
		const { container } = renderHierarchicalControl( {
			selected: hierarchicalList.filter(
				( { id } ) => Number( id ) === 4
			),
		} );

		expandCategory( container, 'Apricots' );

		const apricotsCheckbox = screen.getByRole( 'checkbox', {
			name: 'Apricots',
		} );
		const elderberryCheckbox = screen.getByRole( 'checkbox', {
			name: 'Elderberry',
		} );

		expect( apricotsCheckbox ).toBePartiallyChecked();
		expect( elderberryCheckbox ).toBePartiallyChecked();
	} );
} );
