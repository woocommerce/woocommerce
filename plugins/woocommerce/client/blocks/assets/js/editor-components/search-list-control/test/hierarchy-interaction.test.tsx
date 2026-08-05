/**
 * External dependencies
 */
import { fireEvent, render, screen } from '@testing-library/react';

/**
 * Internal dependencies
 */
import { SearchListControl, SearchListItem } from '..';
import type {
	RenderItemArgs,
	SearchListItem as SearchListItemType,
} from '../types';

const hierarchicalList = [
	{ id: 1, name: 'Apricots', parent: 0, value: 'apricots' },
	{ id: 2, name: 'Clementine', parent: 1, value: 'clementine' },
	{ id: 3, name: 'Elderberry', parent: 1, value: 'elderberry' },
	{ id: 4, name: 'Guava', parent: 3, value: 'guava' },
	{ id: 5, name: 'Lychee', parent: 0, value: 'lychee' },
	{ id: 6, name: 'Mulberry', parent: 0, value: 'mulberry' },
] as SearchListItemType[];

const renderNonSelectableParentItem = ( args: RenderItemArgs ) => (
	<SearchListItem { ...args } isSelectable={ ! args.item.children?.length } />
);

const renderHierarchicalControl = ( {
	selected = [] as SearchListItemType[],
	onChange = jest.fn(),
	renderItem = undefined as
		| ( ( args: RenderItemArgs ) => JSX.Element )
		| undefined,
} = {} ) =>
	render(
		<SearchListControl
			isHierarchical
			isSingle={ false }
			isCompact
			list={ hierarchicalList }
			selected={ selected }
			onChange={ onChange }
			renderItem={ renderItem }
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
			( item: SearchListItemType ) => item.id
		);
		expect( selectedIds ).toEqual( [ 1, 2, 3, 4 ] );
	} );

	test( 'does not collapse an expanded parent when its checkbox is clicked', () => {
		const { container } = renderHierarchicalControl();

		expandCategory( container, 'Apricots' );
		expect(
			screen.getByRole( 'checkbox', { name: 'Elderberry' } )
		).toBeInTheDocument();

		fireEvent.click( screen.getByRole( 'checkbox', { name: 'Apricots' } ) );

		expect(
			screen.getByRole( 'checkbox', { name: 'Elderberry' } )
		).toBeInTheDocument();
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

	describe( 'isSelectable', () => {
		test( 'shows a non-selectable parent as checked when all descendants are selected', () => {
			const { container } = renderHierarchicalControl( {
				selected: hierarchicalList.filter( ( { id } ) =>
					[ 2, 3, 4 ].includes( Number( id ) )
				),
				renderItem: renderNonSelectableParentItem,
			} );

			expandCategory( container, 'Apricots' );

			expect(
				screen.getByRole( 'checkbox', { name: 'Apricots' } )
			).toBeChecked();
		} );

		test( 'shows a non-selectable parent as indeterminate when only some descendants are selected', () => {
			const { container } = renderHierarchicalControl( {
				selected: hierarchicalList.filter(
					( { id } ) => Number( id ) === 4
				),
				renderItem: renderNonSelectableParentItem,
			} );

			expandCategory( container, 'Apricots' );

			expect(
				screen.getByRole( 'checkbox', { name: 'Apricots' } )
			).toBePartiallyChecked();
			expect(
				screen.getByRole( 'checkbox', { name: 'Elderberry' } )
			).toBeChecked();
		} );

		test( 'selects only descendants when a non-selectable parent checkbox is checked', () => {
			const onChange = jest.fn();
			const { container } = renderHierarchicalControl( {
				onChange,
				renderItem: renderNonSelectableParentItem,
			} );

			expandCategory( container, 'Apricots' );
			fireEvent.click(
				screen.getByRole( 'checkbox', { name: 'Apricots' } )
			);

			const selectedIds = onChange.mock.calls[ 0 ][ 0 ].map(
				( item: SearchListItemType ) => item.id
			);
			expect( selectedIds ).toEqual( [ 2, 3, 4 ] );
			expect( selectedIds ).not.toContain( 1 );
		} );

		test( 'deselects only descendants when a non-selectable parent checkbox is unchecked', () => {
			const onChange = jest.fn();
			const selected = hierarchicalList.filter( ( { id } ) =>
				[ 2, 3, 4 ].includes( Number( id ) )
			);
			const { container } = renderHierarchicalControl( {
				onChange,
				selected,
				renderItem: renderNonSelectableParentItem,
			} );

			expandCategory( container, 'Apricots' );
			fireEvent.click(
				screen.getByRole( 'checkbox', { name: 'Apricots' } )
			);

			expect( onChange ).toHaveBeenCalledWith( [] );
		} );

		test( 'selects remaining descendants when a partially selected non-selectable parent is checked', () => {
			const onChange = jest.fn();
			const selected = hierarchicalList.filter(
				( { id } ) => Number( id ) === 4
			);
			const { container } = renderHierarchicalControl( {
				onChange,
				selected,
				renderItem: renderNonSelectableParentItem,
			} );

			expandCategory( container, 'Apricots' );
			fireEvent.click(
				screen.getByRole( 'checkbox', { name: 'Apricots' } )
			);

			const selectedIds = onChange.mock.calls[ 0 ][ 0 ].map(
				( item: SearchListItemType ) => item.id
			);
			expect( selectedIds ).toEqual( [ 4, 2, 3 ] );
			expect( selectedIds ).not.toContain( 1 );
		} );
	} );
} );
