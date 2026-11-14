/**
 * External dependencies
 */
import { render, screen, act } from '@testing-library/react';
import { DndContext } from '@dnd-kit/core';
import '@testing-library/jest-dom';

/**
 * Internal dependencies
 */
import { SortableContainer } from '../sortable-container';

type DndContextMock = jest.Mock & {
	dragStart: jest.Mock;
	dragEnd: jest.Mock;
};

jest.mock( '@dnd-kit/core', () => ( {
	...jest.requireActual( '@dnd-kit/core' ),
	DndContext: jest.fn( ( { children, onDragStart, onDragEnd } ) => {
		( DndContext as unknown as DndContextMock ).dragStart = onDragStart;
		( DndContext as unknown as DndContextMock ).dragEnd = onDragEnd;
		return <div>{ children }</div>;
	} ),
} ) );

describe( 'SortableContainer', () => {
	// Helper function to access the mock
	const getDndMock = () => DndContext as unknown as DndContextMock;

	const mockItems = [
		{ id: '1', name: 'Item 1' },
		{ id: '2', name: 'Item 2' },
		{ id: '3', name: 'Item 3' },
	];

	it( 'should render children components', () => {
		render(
			<SortableContainer items={ mockItems } setItems={ () => {} }>
				<div data-testid="child">Test Child</div>
			</SortableContainer>
		);

		expect( screen.getByTestId( 'child' ) ).toBeInTheDocument();
	} );

	it( 'should apply supplied className', () => {
		const { container } = render(
			<SortableContainer
				items={ mockItems }
				setItems={ () => {} }
				className="custom-class"
			>
				<div>Content</div>
			</SortableContainer>
		);

		expect( container.firstChild ).toHaveClass( 'sortable-container' );
		expect( container.firstChild ).toHaveClass( 'custom-class' );
	} );

	it( 'should call onDragStart and onDragEnd callbacks', () => {
		const onDragStart = jest.fn();
		const onDragEnd = jest.fn();
		const setItems = jest.fn();

		render(
			<SortableContainer
				items={ mockItems }
				setItems={ setItems }
				onDragStart={ onDragStart }
				onDragEnd={ onDragEnd }
			>
				<div>Content</div>
			</SortableContainer>
		);

		const mockDragStartEvent = { active: { id: '1' } };
		const mockDragEndEvent = {
			active: { id: '1' },
			over: { id: '2' },
		};

		act( () => {
			getDndMock().dragStart( mockDragStartEvent );
		} );

		expect( onDragStart ).toHaveBeenCalledWith( mockDragStartEvent );

		act( () => {
			getDndMock().dragEnd( mockDragEndEvent );
		} );

		expect( onDragEnd ).toHaveBeenCalledWith( mockDragEndEvent );
	} );

	it( 'should reorder items on successful drag end', () => {
		const setItems = jest.fn();

		render(
			<SortableContainer items={ mockItems } setItems={ setItems }>
				<div>Content</div>
			</SortableContainer>
		);

		act( () => {
			getDndMock().dragEnd( {
				active: { id: '1' },
				over: { id: '2' },
			} );
		} );

		expect( setItems ).toHaveBeenCalled();
		const newItems = setItems.mock.calls[ 0 ][ 0 ];
		expect( newItems[ 0 ].id ).toBe( '2' );
		expect( newItems[ 1 ].id ).toBe( '1' );
		expect( newItems[ 2 ].id ).toBe( '3' );
	} );

	it( 'should not reorder items when drag ends without a target', () => {
		const setItems = jest.fn();

		render(
			<SortableContainer items={ mockItems } setItems={ setItems }>
				<div>Content</div>
			</SortableContainer>
		);

		act( () => {
			getDndMock().dragEnd( {
				active: { id: '1' },
				over: null,
			} );
		} );

		expect( setItems ).not.toHaveBeenCalled();
	} );

	it( 'should apply dragging class when item is being dragged', () => {
		const { container } = render(
			<SortableContainer items={ mockItems } setItems={ () => {} }>
				<div>Content</div>
			</SortableContainer>
		);

		act( () => {
			getDndMock().dragStart( { active: { id: '1' } } );
		} );

		expect( container.firstChild ).toHaveClass( 'has-dragging-item' );

		act( () => {
			getDndMock().dragEnd( {
				active: { id: '1' },
				over: { id: '2' },
			} );
		} );

		expect( container.firstChild ).not.toHaveClass( 'has-dragging-item' );
	} );
} );
