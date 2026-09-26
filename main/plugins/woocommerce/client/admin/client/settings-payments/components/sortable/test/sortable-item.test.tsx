/**
 * External dependencies
 */
import { render, screen } from '@testing-library/react';
import { useSortable } from '@dnd-kit/sortable';

/**
 * Internal dependencies
 */
import { SortableItem } from '../sortable-item';

jest.mock( '@dnd-kit/sortable', () => ( {
	useSortable: jest.fn(),
} ) );

describe( 'SortableItem', () => {
	const mockUseSortable = {
		attributes: { 'aria-role': 'button' },
		listeners: { onClick: jest.fn() },
		setNodeRef: jest.fn(),
		transform: null,
		transition: 'transform 250ms ease',
		isDragging: false,
	};

	beforeEach( () => {
		( useSortable as jest.Mock ).mockReturnValue( mockUseSortable );
	} );

	it( 'should render children correctly', () => {
		render(
			<SortableItem id="test-id">
				<div>Test Content</div>
			</SortableItem>
		);

		expect( screen.getByText( 'Test Content' ) ).toBeInTheDocument();
	} );

	it( 'should apply supplied className', () => {
		const { container } = render(
			<SortableItem id="test-id" className="custom-class">
				<div>Test Content</div>
			</SortableItem>
		);

		expect( container.firstChild ).toHaveClass( 'custom-class' );
		expect( container.firstChild ).toHaveClass( 'sortable-item' );
	} );

	it( 'should apply dragging class when isDragging is true', () => {
		( useSortable as jest.Mock ).mockReturnValue( {
			...mockUseSortable,
			isDragging: true,
		} );

		const { container } = render(
			<SortableItem id="test-id">
				<div>Test Content</div>
			</SortableItem>
		);

		expect( container.firstChild ).toHaveClass( 'is-dragging' );
	} );

	it( 'should pass additional props to the container div', () => {
		render(
			<SortableItem id="test-id" data-testid="sort-item">
				<div>Test Content</div>
			</SortableItem>
		);

		expect( screen.getByTestId( 'sort-item' ) ).toBeInTheDocument();
	} );
} );
